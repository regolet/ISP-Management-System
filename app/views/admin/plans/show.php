<?php
$title = 'Plan Details - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Plan Details</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/plans" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Plans
            </a>
            <a href="/admin/plans/<?= $plan['id'] ?>/edit" class="btn btn-primary">
                <i class="fa fa-edit"></i> Edit Plan
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Plan Details -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Plan Information</h5>
                    <span class="badge bg-<?= $plan['status'] === 'active' ? 'success' : 'danger' ?>">
                        <?= ucfirst($plan['status']) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Basic Details</h6>
                        <div class="mb-2">
                            <strong>Name:</strong><br>
                            <?= htmlspecialchars($plan['name']) ?>
                        </div>
                        <div class="mb-2">
                            <strong>Description:</strong><br>
                            <?= nl2br(htmlspecialchars($plan['description'])) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Service Details</h6>
                        <div class="mb-2">
                            <strong>Bandwidth:</strong><br>
                            <?= formatBandwidth($plan['bandwidth']) ?>
                        </div>
                        <div class="mb-2">
                            <strong>Monthly Fee:</strong><br>
                            <?= formatCurrency($plan['amount']) ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($plan['features'])): ?>
                    <h6>Features</h6>
                    <ul class="list-group mb-4">
                        <?php foreach ($plan['features'] as $feature): ?>
                            <li class="list-group-item">
                                <i class="fa fa-check text-success me-2"></i>
                                <?= htmlspecialchars($feature) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <h6>Created</h6>
                        <div><?= date('F d, Y h:i A', strtotime($plan['created_at'])) ?></div>
                        <small class="text-muted">by <?= htmlspecialchars($plan['created_by_name']) ?></small>
                    </div>
                    <div class="col-md-6">
                        <h6>Last Updated</h6>
                        <div><?= date('F d, Y h:i A', strtotime($plan['updated_at'])) ?></div>
                        <small class="text-muted">by <?= htmlspecialchars($plan['updated_by_name']) ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Quick Stats -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Statistics</h5>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">Active Subscribers</div>
                            <h3 class="mb-0"><?= $stats['active_subscribers'] ?></h3>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">Total Revenue</div>
                            <h3 class="mb-0"><?= formatCurrency($stats['total_revenue']) ?></h3>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">Avg. Duration</div>
                            <h3 class="mb-0"><?= number_format($stats['avg_duration'], 1) ?></h3>
                            <small class="text-muted">months</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted small">Churn Rate</div>
                            <h3 class="mb-0"><?= number_format($stats['churn_rate'] * 100, 1) ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscribers -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Subscribers</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($subscribers)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($subscribers as $subscriber): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div><?= htmlspecialchars($subscriber['customer_name']) ?></div>
                                        <small class="text-muted">
                                            Since <?= date('M d, Y', strtotime($subscriber['start_date'])) ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php 
                                        echo match($subscriber['status']) {
                                            'active' => 'success',
                                            'suspended' => 'warning',
                                            'terminated' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?= ucfirst($subscriber['status']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($subscribers) >= 5): ?>
                        <div class="card-footer text-center">
                            <a href="/admin/subscriptions?plan_id=<?= $plan['id'] ?>" class="btn btn-sm btn-link">
                                View All Subscribers
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        No subscribers yet
                    </div>
                <?php endif; ?>
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
});
</script>
