<?php
$title = 'Subscription - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>My Subscription</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/customer/subscription/upgrade" class="btn btn-primary">
                <i class="fa fa-arrow-up"></i> Upgrade Plan
            </a>
            <a href="/customer/subscription/usage" class="btn btn-info">
                <i class="fa fa-chart-line"></i> Usage Stats
            </a>
        </div>
    </div>
</div>

<!-- Current Plan -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Current Plan</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <table class="table">
                    <tr>
                        <th width="30%">Plan Name</th>
                        <td>
                            <?= htmlspecialchars($subscription['plan_name']) ?>
                            <?php if ($subscription['is_promotional']): ?>
                                <span class="badge bg-warning">Promotional</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Speed</th>
                        <td>
                            <?= formatBandwidth($subscription['download_speed']) ?> Download /
                            <?= formatBandwidth($subscription['upload_speed']) ?> Upload
                        </td>
                    </tr>
                    <tr>
                        <th>Monthly Fee</th>
                        <td><?= formatCurrency($subscription['monthly_fee']) ?></td>
                    </tr>
                    <tr>
                        <th>Data Cap</th>
                        <td>
                            <?php if ($subscription['data_cap']): ?>
                                <?= formatDataUsage($subscription['data_cap']) ?>
                            <?php else: ?>
                                <span class="badge bg-success">Unlimited</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Contract Period</th>
                        <td>
                            <?= $subscription['contract_period'] ?> months
                            <small class="text-muted d-block">
                                Expires: <?= formatDate($subscription['contract_end_date']) ?>
                            </small>
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge bg-<?= getStatusBadgeClass($subscription['status']) ?>">
                                <?= ucfirst($subscription['status']) ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6>Plan Features</h6>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($subscription['features'] as $feature): ?>
                                <li class="mb-2">
                                    <i class="fa fa-check text-success me-2"></i>
                                    <?= htmlspecialchars($feature) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Service Details -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Service Information</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="40%">Installation Address</th>
                        <td><?= nl2br(htmlspecialchars($subscription['installation_address'])) ?></td>
                    </tr>
                    <tr>
                        <th>Installation Date</th>
                        <td><?= formatDate($subscription['installation_date']) ?></td>
                    </tr>
                    <tr>
                        <th>Service Type</th>
                        <td><?= htmlspecialchars($subscription['service_type']) ?></td>
                    </tr>
                    <tr>
                        <th>Connection Type</th>
                        <td><?= htmlspecialchars($subscription['connection_type']) ?></td>
                    </tr>
                    <tr>
                        <th>IP Address</th>
                        <td>
                            <?= htmlspecialchars($subscription['ip_address']) ?>
                            <?php if ($subscription['is_static_ip']): ?>
                                <span class="badge bg-info">Static</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Equipment Details</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="40%">Router Model</th>
                        <td><?= htmlspecialchars($subscription['router_model']) ?></td>
                    </tr>
                    <tr>
                        <th>Router Serial</th>
                        <td><?= htmlspecialchars($subscription['router_serial']) ?></td>
                    </tr>
                    <?php if ($subscription['ont_model']): ?>
                        <tr>
                            <th>ONT Model</th>
                            <td><?= htmlspecialchars($subscription['ont_model']) ?></td>
                        </tr>
                        <tr>
                            <th>ONT Serial</th>
                            <td><?= htmlspecialchars($subscription['ont_serial']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Equipment Status</th>
                        <td>
                            <span class="badge bg-<?= getEquipmentStatusClass($subscription['equipment_status']) ?>">
                                <?= ucfirst($subscription['equipment_status']) ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Available Plans -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Available Plans</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($availablePlans as $plan): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 <?= $plan['id'] === $subscription['plan_id'] ? 'border-primary' : '' ?>">
                        <div class="card-header text-center">
                            <h5 class="mb-0">
                                <?= htmlspecialchars($plan['name']) ?>
                                <?php if ($plan['id'] === $subscription['plan_id']): ?>
                                    <span class="badge bg-primary">Current Plan</span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <h3 class="mb-0"><?= formatCurrency($plan['monthly_fee']) ?></h3>
                                <small class="text-muted">per month</small>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fa fa-bolt text-primary me-2"></i>
                                    <?= formatBandwidth($plan['download_speed']) ?> Download
                                </li>
                                <li class="mb-2">
                                    <i class="fa fa-upload text-primary me-2"></i>
                                    <?= formatBandwidth($plan['upload_speed']) ?> Upload
                                </li>
                                <li class="mb-2">
                                    <i class="fa fa-database text-primary me-2"></i>
                                    <?= $plan['data_cap'] ? formatDataUsage($plan['data_cap']) : 'Unlimited Data' ?>
                                </li>
                                <?php foreach ($plan['features'] as $feature): ?>
                                    <li class="mb-2">
                                        <i class="fa fa-check text-success me-2"></i>
                                        <?= htmlspecialchars($feature) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-footer text-center">
                            <?php if ($plan['id'] === $subscription['plan_id']): ?>
                                <button type="button" class="btn btn-outline-primary" disabled>
                                    Current Plan
                                </button>
                            <?php else: ?>
                                <a href="/customer/subscription/upgrade/<?= $plan['id'] ?>" class="btn btn-primary">
                                    Upgrade to This Plan
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function formatBandwidth($speed) {
    if ($speed >= 1000) {
        return ($speed / 1000) . ' Gbps';
    }
    return $speed . ' Mbps';
}

function formatDataUsage($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function getStatusBadgeClass($status) {
    return match ($status) {
        'active' => 'success',
        'suspended' => 'warning',
        'terminated' => 'danger',
        'pending' => 'info',
        default => 'secondary'
    };
}

function getEquipmentStatusClass($status) {
    return match ($status) {
        'active' => 'success',
        'faulty' => 'warning',
        'replaced' => 'info',
        'damaged' => 'danger',
        default => 'secondary'
    };
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Plan Comparison
    const comparePlans = document.querySelectorAll('.compare-plan');
    comparePlans.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectedPlans = document.querySelectorAll('.compare-plan:checked');
            if (selectedPlans.length > 3) {
                this.checked = false;
                alert('You can compare up to 3 plans at a time');
                return;
            }
            
            if (selectedPlans.length >= 2) {
                document.getElementById('comparePlansBtn').disabled = false;
            } else {
                document.getElementById('comparePlansBtn').disabled = true;
            }
        });
    });

    // Plan Comparison Modal
    document.getElementById('comparePlansBtn')?.addEventListener('click', function() {
        const selectedPlans = Array.from(document.querySelectorAll('.compare-plan:checked'))
            .map(checkbox => checkbox.value);
        
        if (selectedPlans.length >= 2) {
            window.location.href = '/customer/subscription/compare?' + 
                selectedPlans.map(id => 'plans[]=' + id).join('&');
        }
    });
});
</script>
