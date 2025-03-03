<?php
$title = 'Payment Details - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Payment Details</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/payments" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Payments
            </a>
            <button type="button" class="btn btn-primary" onclick="printReceipt()">
                <i class="fa fa-print"></i> Print Receipt
            </button>
            <a href="/admin/payments/<?= $payment['id'] ?>/receipt" class="btn btn-info">
                <i class="fa fa-download"></i> Download Receipt
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Payment Details -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Payment Information</h5>
                    <span class="badge bg-<?php 
                        echo match($payment['status']) {
                            'completed' => 'success',
                            'pending' => 'warning',
                            'failed' => 'danger',
                            'void' => 'secondary',
                            default => 'secondary'
                        };
                    ?>">
                        <?= ucfirst($payment['status']) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Customer Details</h6>
                        <div class="mb-1"><?= htmlspecialchars($payment['customer_name']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($payment['customer_code']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($payment['email']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($payment['phone']) ?></div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6>Payment Details</h6>
                        <div class="mb-1">Date: <?= date('M d, Y h:i A', strtotime($payment['payment_date'])) ?></div>
                        <div class="mb-1">Method: <?= ucfirst($payment['payment_method']) ?></div>
                        <?php if ($payment['reference_no']): ?>
                            <div>Reference: <?= htmlspecialchars($payment['reference_no']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div>Payment for Invoice #<?= htmlspecialchars($payment['invoiceid']) ?></div>
                                    <?php if ($payment['description']): ?>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($payment['description']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= formatCurrency($payment['amount']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-end border-0"><strong>Total Amount</strong></td>
                                <td class="text-end border-0">
                                    <strong><?= formatCurrency($payment['amount']) ?></strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if ($payment['notes']): ?>
                    <div class="mt-4">
                        <h6>Notes</h6>
                        <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($payment['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Actions -->
        <?php if ($payment['status'] === 'pending'): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="/admin/payments/<?= $payment['id'] ?>/edit" 
                           class="btn btn-primary">
                            <i class="fa fa-edit"></i> Edit Payment
                        </a>
                        <button type="button" class="btn btn-danger" onclick="voidPayment()">
                            <i class="fa fa-ban"></i> Void Payment
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Invoice Details -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Invoice Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="mb-1">Invoice #<?= htmlspecialchars($payment['invoiceid']) ?></div>
                    <div class="text-muted small">
                        Due Date: <?= date('M d, Y', strtotime($payment['due_date'])) ?>
                    </div>
                </div>

                <table class="table table-sm">
                    <tr>
                        <td>Invoice Amount</td>
                        <td class="text-end"><?= formatCurrency($payment['invoice_amount']) ?></td>
                    </tr>
                    <tr>
                        <td>Amount Paid</td>
                        <td class="text-end text-success">
                            <?= formatCurrency($payment['amount_paid']) ?>
                        </td>
                    </tr>
                    <tr class="fw-bold">
                        <td>Balance Due</td>
                        <td class="text-end">
                            <?= formatCurrency($payment['balance_due']) ?>
                        </td>
                    </tr>
                </table>

                <div class="mt-3">
                    <span class="badge bg-<?php 
                        echo match($payment['invoice_status']) {
                            'paid' => 'success',
                            'partial' => 'warning',
                            'unpaid' => 'danger',
                            'void' => 'secondary',
                            default => 'secondary'
                        };
                    ?>">
                        Invoice: <?= ucfirst($payment['invoice_status']) ?>
                    </span>
                </div>

                <div class="mt-3">
                    <a href="/admin/billing/<?= $payment['billing_id'] ?>" 
                       class="btn btn-outline-primary btn-sm w-100">
                        View Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function voidPayment() {
    if (confirm('Are you sure you want to void this payment? This action cannot be undone.')) {
        fetch('/admin/payments/<?= $payment['id'] ?>/void', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to void payment');
            }
        });
    }
}

function printReceipt() {
    window.print();
}
</script>

<style>
@media print {
    .btn-group, .card-header, .actions {
        display: none !important;
    }
    .card {
        border: none !important;
    }
    .card-body {
        padding: 0 !important;
    }
}
</style>
