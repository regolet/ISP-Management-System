<?php
$title = 'Subscription Details - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Subscription Details</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/subscriptions" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Subscriptions
            </a>
            <a href="/admin/subscriptions/<?= $subscription['id'] ?>/edit" class="btn btn-primary">
                <i class="fa fa-edit"></i> Edit Subscription
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Subscription Details -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Service Information</h5>
                    <span class="badge bg-<?php 
                        echo match($subscription['status']) {
                            'active' => 'success',
                            'suspended' => 'warning',
                            'terminated' => 'danger',
                            'pending' => 'info',
                            default => 'secondary'
                        };
                    ?>">
                        <?= ucfirst($subscription['status']) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Customer Details</h6>
                        <div class="mb-1"><?= htmlspecialchars($subscription['customer_name']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($subscription['customer_code']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($subscription['email']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($subscription['phone']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <h6>Plan Details</h6>
                        <div class="mb-1"><?= htmlspecialchars($subscription['plan_name']) ?></div>
                        <div class="text-muted small">
                            Bandwidth: <?= formatBandwidth($subscription['bandwidth']) ?>
                        </div>
                        <div class="text-muted small">
                            Monthly Fee: <?= formatCurrency($subscription['plan_amount']) ?>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Subscription Period</h6>
                        <div class="mb-1">Start Date: <?= date('M d, Y', strtotime($subscription['start_date'])) ?></div>
                        <?php if ($subscription['end_date']): ?>
                            <div class="text-muted small">
                                End Date: <?= date('M d, Y', strtotime($subscription['end_date'])) ?>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small">No end date (Indefinite)</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Installation Address</h6>
                        <div class="text-muted">
                            <?= nl2br(htmlspecialchars($subscription['installation_address'])) ?>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Equipment Details</h6>
                        <div class="mb-1">Router: <?= htmlspecialchars($subscription['router_model']) ?></div>
                        <div class="text-muted small">
                            Serial: <?= htmlspecialchars($subscription['router_serial']) ?>
                        </div>
                        <?php if ($subscription['ont_model']): ?>
                            <div class="mt-2">ONT: <?= htmlspecialchars($subscription['ont_model']) ?></div>
                            <div class="text-muted small">
                                Serial: <?= htmlspecialchars($subscription['ont_serial']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Network Configuration</h6>
                        <div class="mb-1">IP Type: <?= ucfirst($subscription['ip_type']) ?></div>
                        <?php if ($subscription['ip_type'] === 'static'): ?>
                            <div class="text-muted small">
                                IP Address: <?= htmlspecialchars($subscription['ip_address']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($subscription['notes']): ?>
                    <div class="mt-4">
                        <h6>Notes</h6>
                        <p class="text-muted mb-0">
                            <?= nl2br(htmlspecialchars($subscription['notes'])) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Actions -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Actions</h5>
                <div class="d-grid gap-2">
                    <?php if ($subscription['status'] === 'active'): ?>
                        <button type="button" class="btn btn-warning" onclick="suspendSubscription()">
                            <i class="fa fa-pause"></i> Suspend Subscription
                        </button>
                    <?php elseif ($subscription['status'] === 'suspended'): ?>
                        <button type="button" class="btn btn-success" onclick="activateSubscription()">
                            <i class="fa fa-play"></i> Activate Subscription
                        </button>
                    <?php endif; ?>
                    <?php if ($subscription['status'] !== 'terminated'): ?>
                        <button type="button" class="btn btn-danger" onclick="terminateSubscription()">
                            <i class="fa fa-stop"></i> Terminate Subscription
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Billing History -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Billing</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($bills)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($bills as $bill): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?= htmlspecialchars($bill['invoiceid']) ?>
                                        </h6>
                                        <small class="text-muted">
                                            Due: <?= date('M d, Y', strtotime($bill['due_date'])) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold">
                                            <?= formatCurrency($bill['amount']) ?>
                                        </div>
                                        <span class="badge bg-<?php 
                                            echo match($bill['status']) {
                                                'paid' => 'success',
                                                'partial' => 'warning',
                                                'unpaid' => 'danger',
                                                'void' => 'secondary',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?= ucfirst($bill['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="/admin/billing?customer_id=<?= $subscription['customer_id'] ?>" 
                           class="btn btn-sm btn-outline-primary">
                            View All Billing History
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        No billing history found
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function suspendSubscription() {
    updateSubscriptionStatus('suspended', 'suspend');
}

function activateSubscription() {
    updateSubscriptionStatus('active', 'activate');
}

function terminateSubscription() {
    if (confirm('Are you sure you want to terminate this subscription? This action cannot be undone.')) {
        updateSubscriptionStatus('terminated', 'terminate');
    }
}

function updateSubscriptionStatus(status, action) {
    if (confirm(`Are you sure you want to ${action} this subscription?`)) {
        fetch('/admin/subscriptions/<?= $subscription['id'] ?>/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || `Failed to ${action} subscription`);
            }
        });
    }
}
</script>
