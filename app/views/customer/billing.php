<?php
$title = 'Billing - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Billing</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/customer/billing/pay" class="btn btn-success">
                <i class="fa fa-credit-card"></i> Make Payment
            </a>
            <button type="button" class="btn btn-primary" id="downloadStatement">
                <i class="fa fa-download"></i> Download Statement
            </button>
        </div>
    </div>
</div>

<!-- Account Summary -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card <?= $billing['balance'] > 0 ? 'bg-warning' : 'bg-success' ?> text-white">
            <div class="card-body">
                <h5 class="card-title">Current Balance</h5>
                <h3 class="card-text"><?= formatCurrency(abs($billing['balance'])) ?></h3>
                <?php if ($billing['balance'] > 0): ?>
                    <p class="mb-0">Due by <?= formatDate($billing['due_date']) ?></p>
                <?php else: ?>
                    <p class="mb-0">Account Paid</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Monthly Plan</h5>
                <h3 class="card-text"><?= formatCurrency($subscription['monthly_fee']) ?></h3>
                <p class="mb-0"><?= htmlspecialchars($subscription['plan_name']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Last Payment</h5>
                <h3 class="card-text"><?= formatCurrency($lastPayment['amount']) ?></h3>
                <p class="mb-0"><?= formatDate($lastPayment['date']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <h5 class="card-title">Next Bill</h5>
                <h3 class="card-text"><?= formatCurrency($nextBill['amount']) ?></h3>
                <p class="mb-0">Due <?= formatDate($nextBill['due_date']) ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Current Bill -->
<?php if ($currentBill): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Current Bill</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <table class="table">
                    <tr>
                        <th width="30%">Bill Period</th>
                        <td>
                            <?= formatDate($currentBill['period_start']) ?> - 
                            <?= formatDate($currentBill['period_end']) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Due Date</th>
                        <td>
                            <?= formatDate($currentBill['due_date']) ?>
                            <?php if (strtotime($currentBill['due_date']) < time()): ?>
                                <span class="badge bg-danger">Overdue</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge bg-<?= getBillStatusClass($currentBill['status']) ?>">
                                <?= ucfirst($currentBill['status']) ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Bill Summary</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Monthly Plan</td>
                                <td class="text-end"><?= formatCurrency($currentBill['plan_fee']) ?></td>
                            </tr>
                            <?php if ($currentBill['additional_charges']): ?>
                                <tr>
                                    <td>Additional Charges</td>
                                    <td class="text-end"><?= formatCurrency($currentBill['additional_charges']) ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($currentBill['discounts']): ?>
                                <tr>
                                    <td>Discounts</td>
                                    <td class="text-end text-success">
                                        -<?= formatCurrency($currentBill['discounts']) ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($currentBill['tax']): ?>
                                <tr>
                                    <td>Tax</td>
                                    <td class="text-end"><?= formatCurrency($currentBill['tax']) ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="table-active fw-bold">
                                <td>Total Amount</td>
                                <td class="text-end"><?= formatCurrency($currentBill['total_amount']) ?></td>
                            </tr>
                            <?php if ($currentBill['amount_paid']): ?>
                                <tr>
                                    <td>Amount Paid</td>
                                    <td class="text-end text-success">
                                        -<?= formatCurrency($currentBill['amount_paid']) ?>
                                    </td>
                                </tr>
                                <tr class="fw-bold">
                                    <td>Balance Due</td>
                                    <td class="text-end">
                                        <?= formatCurrency($currentBill['balance_due']) ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                        <?php if ($currentBill['balance_due'] > 0): ?>
                            <a href="/customer/billing/pay" class="btn btn-success w-100">
                                <i class="fa fa-credit-card"></i> Pay Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Billing History -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Billing History</h5>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-period="3">3 Months</button>
            <button type="button" class="btn btn-sm btn-outline-secondary active" data-period="6">6 Months</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-period="12">12 Months</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Bill #</th>
                        <th>Period</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($billingHistory as $bill): ?>
                        <tr>
                            <td><?= htmlspecialchars($bill['bill_number']) ?></td>
                            <td>
                                <?= formatDate($bill['period_start']) ?> - 
                                <?= formatDate($bill['period_end']) ?>
                            </td>
                            <td>
                                <?= formatDate($bill['due_date']) ?>
                                <?php if ($bill['status'] === 'overdue'): ?>
                                    <span class="badge bg-danger">Overdue</span>
                                <?php endif; ?>
                            </td>
                            <td><?= formatCurrency($bill['total_amount']) ?></td>
                            <td><?= formatCurrency($bill['amount_paid']) ?></td>
                            <td><?= formatCurrency($bill['balance_due']) ?></td>
                            <td>
                                <span class="badge bg-<?= getBillStatusClass($bill['status']) ?>">
                                    <?= ucfirst($bill['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-info view-bill" 
                                            data-id="<?= $bill['id'] ?>" title="View Details">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <a href="/customer/billing/download/<?= $bill['id'] ?>" 
                                       class="btn btn-primary" title="Download PDF">
                                        <i class="fa fa-download"></i>
                                    </a>
                                    <?php if ($bill['balance_due'] > 0): ?>
                                        <a href="/customer/billing/pay/<?= $bill['id'] ?>" 
                                           class="btn btn-success" title="Pay Now">
                                            <i class="fa fa-credit-card"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bill Details Modal -->
<div class="modal fade" id="billModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bill Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="billDetails">
                <!-- Details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<?php
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function getBillStatusClass($status) {
    return match ($status) {
        'paid' => 'success',
        'pending' => 'warning',
        'overdue' => 'danger',
        'partial' => 'info',
        default => 'secondary'
    };
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Period Selection
    document.querySelectorAll('[data-period]').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('[data-period]').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update table data
            const months = this.dataset.period;
            window.location.href = `/customer/billing?months=${months}`;
        });
    });

    // View Bill Details
    const billModal = new bootstrap.Modal(document.getElementById('billModal'));
    document.querySelectorAll('.view-bill').forEach(button => {
        button.addEventListener('click', function() {
            const billId = this.dataset.id;
            
            fetch(`/customer/billing/${billId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('billDetails').innerHTML = `
                        <div class="row">
                            <div class="col-md-8">
                                <table class="table">
                                    <tr>
                                        <th width="30%">Bill Number</th>
                                        <td>${data.bill_number}</td>
                                    </tr>
                                    <tr>
                                        <th>Bill Period</th>
                                        <td>${formatDate(data.period_start)} - ${formatDate(data.period_end)}</td>
                                    </tr>
                                    <tr>
                                        <th>Due Date</th>
                                        <td>${formatDate(data.due_date)}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge bg-${getBillStatusClass(data.status)}">
                                                ${data.status}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Bill Summary</h6>
                                        <table class="table table-sm">
                                            <tr>
                                                <td>Plan Fee</td>
                                                <td class="text-end">${formatCurrency(data.plan_fee)}</td>
                                            </tr>
                                            ${data.additional_charges ? `
                                                <tr>
                                                    <td>Additional</td>
                                                    <td class="text-end">${formatCurrency(data.additional_charges)}</td>
                                                </tr>
                                            ` : ''}
                                            ${data.discounts ? `
                                                <tr>
                                                    <td>Discounts</td>
                                                    <td class="text-end text-success">
                                                        -${formatCurrency(data.discounts)}
                                                    </td>
                                                </tr>
                                            ` : ''}
                                            ${data.tax ? `
                                                <tr>
                                                    <td>Tax</td>
                                                    <td class="text-end">${formatCurrency(data.tax)}</td>
                                                </tr>
                                            ` : ''}
                                            <tr class="table-active fw-bold">
                                                <td>Total</td>
                                                <td class="text-end">${formatCurrency(data.total_amount)}</td>
                                            </tr>
                                            ${data.amount_paid ? `
                                                <tr>
                                                    <td>Paid</td>
                                                    <td class="text-end text-success">
                                                        -${formatCurrency(data.amount_paid)}
                                                    </td>
                                                </tr>
                                                <tr class="fw-bold">
                                                    <td>Balance</td>
                                                    <td class="text-end">${formatCurrency(data.balance_due)}</td>
                                                </tr>
                                            ` : ''}
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    billModal.show();
                });
        });
    });

    // Download Statement
    document.getElementById('downloadStatement').addEventListener('click', function() {
        const period = document.querySelector('[data-period].active').dataset.period;
        window.location.href = `/customer/billing/statement/download?months=${period}`;
    });
});

function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

function getBillStatusClass(status) {
    const classes = {
        'paid': 'success',
        'pending': 'warning',
        'overdue': 'danger',
        'partial': 'info'
    };
    return classes[status] || 'secondary';
}
</script>
