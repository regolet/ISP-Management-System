<?php
$title = 'Payments - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Payments</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/customer/payments/make" class="btn btn-success">
                <i class="fa fa-credit-card"></i> Make Payment
            </a>
            <button type="button" class="btn btn-primary" id="downloadHistory">
                <i class="fa fa-download"></i> Download History
            </button>
        </div>
    </div>
</div>

<!-- Payment Methods -->
<div class="row mb-4">
    <?php foreach ($paymentMethods as $method): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <i class="fa fa-<?= getPaymentMethodIcon($method['type']) ?> fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-0"><?= htmlspecialchars($method['name']) ?></h5>
                            <small class="text-muted">
                                <?= htmlspecialchars($method['description']) ?>
                            </small>
                        </div>
                    </div>
                    <?php if ($method['type'] === 'card'): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>**** **** **** <?= htmlspecialchars($method['last4']) ?></span>
                                <span><?= htmlspecialchars($method['expiry']) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="text-end">
                        <?php if ($method['is_default']): ?>
                            <span class="badge bg-success">Default Method</span>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-primary set-default" 
                                    data-id="<?= $method['id'] ?>">
                                Set as Default
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-method" 
                                data-id="<?= $method['id'] ?>">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center justify-content-center">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMethodModal">
                    <i class="fa fa-plus"></i> Add Payment Method
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Recent Payments -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Payments</h5>
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
                        <th>Date</th>
                        <th>Receipt #</th>
                        <th>Method</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= formatDate($payment['payment_date']) ?></td>
                            <td>
                                <?php if ($payment['receipt_number']): ?>
                                    <a href="/customer/payments/receipt/<?= $payment['id'] ?>" target="_blank">
                                        <?= htmlspecialchars($payment['receipt_number']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="fa fa-<?= getPaymentMethodIcon($payment['method_type']) ?> me-1"></i>
                                <?= htmlspecialchars($payment['method_name']) ?>
                                <?php if ($payment['last4']): ?>
                                    <small class="text-muted">(*<?= $payment['last4'] ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($payment['description']) ?></td>
                            <td><?= formatCurrency($payment['amount']) ?></td>
                            <td>
                                <span class="badge bg-<?= getPaymentStatusClass($payment['status']) ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-info view-payment" 
                                            data-id="<?= $payment['id'] ?>" title="View Details">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <?php if ($payment['receipt_number']): ?>
                                        <a href="/customer/payments/receipt/<?= $payment['id'] ?>" 
                                           class="btn btn-primary" title="Download Receipt" target="_blank">
                                            <i class="fa fa-download"></i>
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

<!-- Add Payment Method Modal -->
<div class="modal fade" id="addMethodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addMethodForm" method="POST" action="/customer/payments/methods/add">
                <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>
                
                <div class="modal-header">
                    <h5 class="modal-title">Add Payment Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Payment Method Type</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="method_type" id="type_card" value="card" checked>
                            <label class="btn btn-outline-primary" for="type_card">
                                <i class="fa fa-credit-card"></i> Credit Card
                            </label>
                            
                            <input type="radio" class="btn-check" name="method_type" id="type_bank" value="bank">
                            <label class="btn btn-outline-primary" for="type_bank">
                                <i class="fa fa-university"></i> Bank Account
                            </label>
                        </div>
                    </div>

                    <!-- Credit Card Fields -->
                    <div id="cardFields">
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Card Number *</label>
                            <input type="text" class="form-control" id="card_number" name="card_number" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label for="expiry" class="form-label">Expiry Date *</label>
                                <input type="text" class="form-control" id="expiry" name="expiry" 
                                       placeholder="MM/YY" required>
                            </div>
                            <div class="col">
                                <label for="cvv" class="form-label">CVV *</label>
                                <input type="text" class="form-control" id="cvv" name="cvv" required>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Account Fields -->
                    <div id="bankFields" style="display: none;">
                        <div class="mb-3">
                            <label for="account_name" class="form-label">Account Name *</label>
                            <input type="text" class="form-control" id="account_name" name="account_name">
                        </div>
                        <div class="mb-3">
                            <label for="account_number" class="form-label">Account Number *</label>
                            <input type="text" class="form-control" id="account_number" name="account_number">
                        </div>
                        <div class="mb-3">
                            <label for="routing_number" class="form-label">Routing Number *</label>
                            <input type="text" class="form-control" id="routing_number" name="routing_number">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="set_default" name="set_default">
                            <label class="form-check-label" for="set_default">
                                Set as default payment method
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Method</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentDetails">
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

function getPaymentMethodIcon($type) {
    return match ($type) {
        'card' => 'credit-card',
        'bank' => 'university',
        'wallet' => 'wallet',
        default => 'money-bill'
    };
}

function getPaymentStatusClass($status) {
    return match ($status) {
        'completed' => 'success',
        'pending' => 'warning',
        'failed' => 'danger',
        'processing' => 'info',
        default => 'secondary'
    };
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment Method Type Toggle
    document.querySelectorAll('input[name="method_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('cardFields').style.display = 
                this.value === 'card' ? 'block' : 'none';
            document.getElementById('bankFields').style.display = 
                this.value === 'bank' ? 'block' : 'none';
            
            // Toggle required fields
            const cardFields = document.querySelectorAll('#cardFields input');
            const bankFields = document.querySelectorAll('#bankFields input');
            
            cardFields.forEach(field => field.required = (this.value === 'card'));
            bankFields.forEach(field => field.required = (this.value === 'bank'));
        });
    });

    // Credit Card Validation
    const cardNumber = document.getElementById('card_number');
    const expiry = document.getElementById('expiry');
    const cvv = document.getElementById('cvv');

    if (cardNumber) {
        cardNumber.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            value = value.replace(/(\d{4})/g, '$1 ').trim();
            this.value = value;
        });
    }

    if (expiry) {
        expiry.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0,2) + '/' + value.slice(2,4);
            }
            this.value = value;
        });
    }

    if (cvv) {
        cvv.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0,4);
        });
    }

    // Set Default Payment Method
    document.querySelectorAll('.set-default').forEach(button => {
        button.addEventListener('click', function() {
            const methodId = this.dataset.id;
            
            fetch('/customer/payments/methods/default', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                },
                body: JSON.stringify({ method_id: methodId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to set default payment method');
                }
            });
        });
    });

    // Remove Payment Method
    document.querySelectorAll('.remove-method').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove this payment method?')) {
                const methodId = this.dataset.id;
                
                fetch('/customer/payments/methods/' + methodId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to remove payment method');
                    }
                });
            }
        });
    });

    // View Payment Details
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    document.querySelectorAll('.view-payment').forEach(button => {
        button.addEventListener('click', function() {
            const paymentId = this.dataset.id;
            
            fetch('/customer/payments/' + paymentId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('paymentDetails').innerHTML = `
                        <table class="table">
                            <tr>
                                <th width="35%">Receipt Number</th>
                                <td>${data.receipt_number || '-'}</td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td>${formatDate(data.payment_date)}</td>
                            </tr>
                            <tr>
                                <th>Amount</th>
                                <td>${formatCurrency(data.amount)}</td>
                            </tr>
                            <tr>
                                <th>Method</th>
                                <td>
                                    <i class="fa fa-${getPaymentMethodIcon(data.method_type)}"></i>
                                    ${data.method_name}
                                    ${data.last4 ? `<small class="text-muted">(*${data.last4})</small>` : ''}
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge bg-${getPaymentStatusClass(data.status)}">
                                        ${data.status}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>${data.description}</td>
                            </tr>
                            ${data.reference_number ? `
                                <tr>
                                    <th>Reference Number</th>
                                    <td>${data.reference_number}</td>
                                </tr>
                            ` : ''}
                        </table>
                    `;
                    paymentModal.show();
                });
        });
    });

    // Period Selection
    document.querySelectorAll('[data-period]').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('[data-period]').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            const months = this.dataset.period;
            window.location.href = `/customer/payments?months=${months}`;
        });
    });

    // Download History
    document.getElementById('downloadHistory').addEventListener('click', function() {
        const period = document.querySelector('[data-period].active').dataset.period;
        window.location.href = `/customer/payments/history/download?months=${period}`;
    });
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function getPaymentMethodIcon(type) {
    const icons = {
        'card': 'credit-card',
        'bank': 'university',
        'wallet': 'wallet'
    };
    return icons[type] || 'money-bill';
}

function getPaymentStatusClass(status) {
    const classes = {
        'completed': 'success',
        'pending': 'warning',
        'failed': 'danger',
        'processing': 'info'
    };
    return classes[status] || 'secondary';
}
</script>
