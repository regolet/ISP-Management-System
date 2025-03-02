<?php
$title = 'Invoice Details - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Invoice Details</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/billing" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Billing
            </a>
            <button type="button" class="btn btn-primary" onclick="printInvoice()">
                <i class="fa fa-print"></i> Print
            </button>
            <a href="/admin/billing/<?= $bill['id'] ?>/pdf" class="btn btn-info">
                <i class="fa fa-download"></i> Download PDF
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Invoice Details -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Invoice #<?= htmlspecialchars($bill['invoiceid']) ?></h5>
                    <span class="badge bg-<?php 
                        echo match($bill['status']) {
                            'paid' => 'success',
                            'unpaid' => 'danger',
                            'partial' => 'warning',
                            'void' => 'secondary',
                            default => 'secondary'
                        };
                    ?>">
                        <?= ucfirst($bill['status']) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Customer Details</h6>
                        <div class="mb-1"><?= htmlspecialchars($bill['customer_name']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($bill['customer_code']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($bill['email']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($bill['phone']) ?></div>
                        <div class="text-muted small"><?= nl2br(htmlspecialchars($bill['address'])) ?></div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6>Invoice Details</h6>
                        <div class="mb-1">Date: <?= date('M d, Y', strtotime($bill['created_at'])) ?></div>
                        <div class="mb-1">Due Date: <?= date('M d, Y', strtotime($bill['due_date'])) ?></div>
                        <?php if ($bill['status'] === 'paid'): ?>
                            <div class="text-success">Paid Date: <?= date('M d, Y', strtotime($bill['paid_date'])) ?></div>
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
                            <!-- Plan Charge -->
                            <tr>
                                <td>
                                    <div><?= htmlspecialchars($bill['plan_name']) ?> Subscription</div>
                                    <small class="text-muted">Monthly Service Fee</small>
                                </td>
                                <td class="text-end"><?= formatCurrency($bill['plan_amount']) ?></td>
                            </tr>
                            
                            <!-- Additional Items -->
                            <?php foreach ($bill['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($item['description']) ?>
                                    </td>
                                    <td class="text-end"><?= formatCurrency($item['amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Summary -->
                            <tr>
                                <td class="text-end border-0"><strong>Subtotal</strong></td>
                                <td class="text-end border-0"><?= formatCurrency($bill['subtotal']) ?></td>
                            </tr>
                            <?php if ($bill['tax_amount']): ?>
                                <tr>
                                    <td class="text-end border-0"><strong>Tax (<?= $bill['tax_rate'] ?>%)</strong></td>
                                    <td class="text-end border-0"><?= formatCurrency($bill['tax_amount']) ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="text-end"><strong>Total Amount</strong></td>
                                <td class="text-end"><strong><?= formatCurrency($bill['amount']) ?></strong></td>
                            </tr>
                            <?php if ($bill['amount_paid']): ?>
                                <tr>
                                    <td class="text-end text-success"><strong>Amount Paid</strong></td>
                                    <td class="text-end text-success">
                                        <strong><?= formatCurrency($bill['amount_paid']) ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end"><strong>Balance Due</strong></td>
                                    <td class="text-end">
                                        <strong><?= formatCurrency($bill['balance_due']) ?></strong>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($bill['notes']): ?>
                    <div class="mt-4">
                        <h6>Notes</h6>
                        <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($bill['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Actions -->
        <?php if ($bill['status'] !== 'paid' && $bill['status'] !== 'void'): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="/admin/payments/create?billing_id=<?= $bill['id'] ?>" 
                           class="btn btn-success">
                            <i class="fa fa-money-bill"></i> Record Payment
                        </a>
                        <a href="/admin/billing/<?= $bill['id'] ?>/edit" 
                           class="btn btn-primary">
                            <i class="fa fa-edit"></i> Edit Invoice
                        </a>
                        <button type="button" class="btn btn-danger" onclick="voidBill()">
                            <i class="fa fa-ban"></i> Void Invoice
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Payment History -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Payment History</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($payments)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($payments as $payment): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?= formatCurrency($payment['amount']) ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?= date('M d, Y', strtotime($payment['payment_date'])) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?= $payment['status'] === 'completed' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($payment['status']) ?>
                                        </span>
                                        <div class="small text-muted">
                                            <?= ucfirst($payment['payment_method']) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($payment['reference_no']): ?>
                                    <div class="small text-muted mt-1">
                                        Ref: <?= htmlspecialchars($payment['reference_no']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        No payments recorded
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function voidBill() {
    if (confirm('Are you sure you want to void this invoice? This action cannot be undone.')) {
        fetch('/admin/billing/<?= $bill['id'] ?>/void', {
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
                alert(data.error || 'Failed to void invoice');
            }
        });
    }
}

function printInvoice() {
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
