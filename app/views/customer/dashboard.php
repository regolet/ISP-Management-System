<?php
$title = 'Dashboard - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Welcome, <?= htmlspecialchars($customer['first_name']) ?></h2>
        <p class="text-muted">Account #: <?= htmlspecialchars($customer['account_number']) ?></p>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/customer/support/new" class="btn btn-primary">
                <i class="fa fa-headset"></i> Get Support
            </a>
            <a href="/customer/billing/pay" class="btn btn-success">
                <i class="fa fa-credit-card"></i> Make Payment
            </a>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <!-- Current Plan -->
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Current Plan</h5>
                <h3 class="card-text"><?= htmlspecialchars($subscription['plan_name']) ?></h3>
                <p class="mb-0">
                    <?= formatBandwidth($subscription['bandwidth']) ?> |
                    <?= formatCurrency($subscription['monthly_fee']) ?>/mo
                </p>
            </div>
        </div>
    </div>

    <!-- Account Balance -->
    <div class="col-md-3">
        <div class="card <?= $billing['balance'] > 0 ? 'bg-warning' : 'bg-success' ?> text-white">
            <div class="card-body">
                <h5 class="card-title">Account Balance</h5>
                <h3 class="card-text"><?= formatCurrency(abs($billing['balance'])) ?></h3>
                <p class="mb-0">
                    <?= $billing['balance'] > 0 ? 'Due ' . formatDate($billing['due_date']) : 'Paid' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Data Usage -->
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Data Usage</h5>
                <h3 class="card-text"><?= formatDataUsage($usage['current']) ?></h3>
                <p class="mb-0">
                    of <?= formatDataUsage($usage['limit']) ?> |
                    <?= number_format($usage['percentage'], 1) ?>%
                </p>
            </div>
        </div>
    </div>

    <!-- Connection Status -->
    <div class="col-md-3">
        <div class="card bg-<?= $connection['status'] === 'active' ? 'success' : 'danger' ?> text-white">
            <div class="card-body">
                <h5 class="card-title">Connection Status</h5>
                <h3 class="card-text"><?= ucfirst($connection['status']) ?></h3>
                <p class="mb-0">
                    <?= formatSpeed($connection['current_speed']) ?> |
                    <?= $connection['uptime'] ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity and Alerts -->
<div class="row mb-4">
    <!-- Recent Activity -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Activity</h5>
                <a href="/customer/activity" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?= htmlspecialchars($activity['description']) ?></h6>
                                <small class="text-muted">
                                    <?= timeAgo($activity['created_at']) ?>
                                </small>
                            </div>
                            <?php if (!empty($activity['details'])): ?>
                                <p class="mb-1 text-muted">
                                    <?= htmlspecialchars($activity['details']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts and Notifications -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Alerts & Notifications</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="alert-icon bg-<?= $alert['type'] ?> text-white rounded p-2 me-3">
                                    <i class="fa fa-<?= getAlertIcon($alert['type']) ?>"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($alert['title']) ?></h6>
                                    <p class="mb-0 text-muted">
                                        <?= htmlspecialchars($alert['message']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Usage Graph -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Data Usage History</h5>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-period="daily">Daily</button>
            <button type="button" class="btn btn-outline-secondary btn-sm active" data-period="weekly">Weekly</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-period="monthly">Monthly</button>
        </div>
    </div>
    <div class="card-body">
        <canvas id="usageChart" height="300"></canvas>
    </div>
</div>

<!-- Quick Links -->
<div class="row">
    <div class="col-md-3">
        <a href="/customer/subscription/upgrade" class="card text-center h-100 text-decoration-none">
            <div class="card-body">
                <i class="fa fa-arrow-up fa-3x text-primary mb-3"></i>
                <h5>Upgrade Plan</h5>
                <p class="text-muted mb-0">Explore faster plans</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/customer/support/tickets" class="card text-center h-100 text-decoration-none">
            <div class="card-body">
                <i class="fa fa-ticket fa-3x text-info mb-3"></i>
                <h5>Support Tickets</h5>
                <p class="text-muted mb-0">View your tickets</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/customer/billing/statements" class="card text-center h-100 text-decoration-none">
            <div class="card-body">
                <i class="fa fa-file-invoice fa-3x text-success mb-3"></i>
                <h5>Billing Statements</h5>
                <p class="text-muted mb-0">View & download</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="/customer/profile" class="card text-center h-100 text-decoration-none">
            <div class="card-body">
                <i class="fa fa-user-cog fa-3x text-warning mb-3"></i>
                <h5>Account Settings</h5>
                <p class="text-muted mb-0">Manage your account</p>
            </div>
        </a>
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

function formatSpeed($speed) {
    if ($speed >= 1000) {
        return round($speed / 1000, 1) . ' Gbps';
    }
    return $speed . ' Mbps';
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function getAlertIcon($type) {
    return match ($type) {
        'success' => 'check-circle',
        'warning' => 'exclamation-triangle',
        'danger' => 'times-circle',
        'info' => 'info-circle',
        default => 'bell'
    };
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Usage Chart
    const ctx = document.getElementById('usageChart').getContext('2d');
    const usageData = <?= json_encode($usageHistory) ?>;
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: usageData.labels,
            datasets: [{
                label: 'Data Usage',
                data: usageData.data,
                borderColor: '#0d6efd',
                tension: 0.1,
                fill: true,
                backgroundColor: 'rgba(13, 110, 253, 0.1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatDataUsage(value);
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Usage: ' + formatDataUsage(context.raw);
                        }
                    }
                }
            }
        }
    });

    // Period Selection
    document.querySelectorAll('[data-period]').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('[data-period]').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Update chart data
            const period = this.dataset.period;
            fetch(`/customer/usage/${period}`)
                .then(response => response.json())
                .then(data => {
                    chart.data.labels = data.labels;
                    chart.data.datasets[0].data = data.data;
                    chart.update();
                });
        });
    });

    function formatDataUsage(bytes) {
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let value = Math.abs(Number(bytes));
        let unitIndex = 0;
        
        while (value >= 1024 && unitIndex < units.length - 1) {
            value /= 1024;
            unitIndex++;
        }
        
        return value.toFixed(2) + ' ' + units[unitIndex];
    }
});
</script>
