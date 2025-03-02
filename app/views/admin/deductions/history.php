<?php
$title = 'Deduction History - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Deduction History</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/deductions" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Deductions
            </a>
            <button type="button" class="btn btn-success" id="exportHistory">
                <i class="fa fa-download"></i> Export History
            </button>
        </div>
    </div>
</div>

<!-- Deduction Details -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>Employee Details</h5>
                <div class="mb-1">
                    <strong><?= htmlspecialchars($deduction['employee_name']) ?></strong>
                </div>
                <div class="text-muted">
                    <?= htmlspecialchars($deduction['employee_code']) ?> - 
                    <?= htmlspecialchars($deduction['department']) ?>
                </div>
            </div>
            <div class="col-md-6">
                <h5>Deduction Details</h5>
                <div class="mb-1">
                    <strong><?= htmlspecialchars($deduction['deduction_name']) ?></strong>
                    <span class="badge bg-<?= $deduction['type'] === 'government' ? 'info' : 'warning' ?>">
                        <?= ucfirst($deduction['type']) ?>
                    </span>
                </div>
                <div class="text-muted">
                    <?= formatCurrency($deduction['amount']) ?> - 
                    <?= ucfirst($deduction['frequency']) ?>
                </div>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-4">
                <h6>Period</h6>
                <div>From: <?= date('F d, Y', strtotime($deduction['start_date'])) ?></div>
                <?php if ($deduction['end_date']): ?>
                    <div>To: <?= date('F d, Y', strtotime($deduction['end_date'])) ?></div>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <h6>Status</h6>
                <span class="badge bg-<?php 
                    echo match($deduction['status']) {
                        'active' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'secondary'
                    };
                ?>">
                    <?= ucfirst($deduction['status']) ?>
                </span>
            </div>
            <div class="col-md-4">
                <h6>Total Deducted</h6>
                <strong><?= formatCurrency($total_deducted) ?></strong>
            </div>
        </div>

        <?php if ($deduction['remarks']): ?>
            <hr>
            <h6>Remarks</h6>
            <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($deduction['remarks'])) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Deduction History -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Transaction History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Payroll Period</th>
                        <th>Amount</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($history)): ?>
                        <?php foreach ($history as $transaction): ?>
                            <tr>
                                <td>
                                    <?= date('M d, Y', strtotime($transaction['transaction_date'])) ?>
                                </td>
                                <td>
                                    <div>
                                        <?= date('M d', strtotime($transaction['period_start'])) ?> - 
                                        <?= date('M d, Y', strtotime($transaction['period_end'])) ?>
                                    </div>
                                    <small class="text-muted">
                                        Pay Date: <?= date('M d, Y', strtotime($transaction['pay_date'])) ?>
                                    </small>
                                </td>
                                <td><?= formatCurrency($transaction['amount']) ?></td>
                                <td>
                                    <?php if ($transaction['payroll_id']): ?>
                                        <a href="/admin/payroll/<?= $transaction['payroll_id'] ?>" 
                                           class="text-decoration-none">
                                            Payroll #<?= $transaction['payroll_id'] ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($transaction['reference']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($transaction['status']) {
                                            'completed' => 'success',
                                            'pending' => 'warning',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?= ucfirst($transaction['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($transaction['notes']): ?>
                                        <button type="button" class="btn btn-link btn-sm p-0" 
                                                data-bs-toggle="tooltip" 
                                                title="<?= htmlspecialchars($transaction['notes']) ?>">
                                            <i class="fa fa-info-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fa fa-info-circle text-muted mb-2"></i>
                                <p class="text-muted mb-0">No transaction history found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Summary Stats -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Transactions</h6>
                <h3 class="mb-0"><?= $stats['total_transactions'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Amount</h6>
                <h3 class="mb-0"><?= formatCurrency($stats['total_amount']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Average Amount</h6>
                <h3 class="mb-0"><?= formatCurrency($stats['average_amount']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Remaining Balance</h6>
                <h3 class="mb-0"><?= formatCurrency($stats['remaining_balance']) ?></h3>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    // Export history
    document.getElementById('exportHistory').addEventListener('click', function() {
        window.location.href = `/admin/deductions/<?= $deduction['id'] ?>/history/export`;
    });
});
</script>
