<?php
$title = 'Admin Dashboard';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class='bx bx-grid-alt'></i> Dashboard</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary" onclick="refreshCharts()">
                <i class='bx bx-refresh'></i> Refresh Data
            </button>
            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class='bx bx-calendar'></i> Period: <span id="selectedPeriod">This Month</span>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="changePeriod('week')">This Week</a></li>
                <li><a class="dropdown-item" href="#" onclick="changePeriod('month')">This Month</a></li>
                <li><a class="dropdown-item" href="#" onclick="changePeriod('year')">This Year</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Customers</h6>
                        <h4 class="mb-0"><?= number_format($stats['total_customers'] ?? 0) ?></h4>
                        <small class="text-success">
                            <i class='bx bx-user'></i> <?= number_format($stats['active_customers'] ?? 0) ?> Active
                        </small>
                    </div>
                    <div class="text-primary">
                        <i class='bx bx-group fs-1'></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Monthly Revenue</h6>
                        <h4 class="mb-0"><?= formatCurrency($stats['monthly_revenue'] ?? 0) ?></h4>
                        <small class="text-info">
                            <i class='bx bx-trending-up'></i> Total: <?= formatCurrency($stats['total_revenue'] ?? 0) ?>
                        </small>
                    </div>
                    <div class="text-success">
                        <i class='bx bx-money fs-1'></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Active Subscriptions</h6>
                        <h4 class="mb-0"><?= number_format($stats['active_subscriptions'] ?? 0) ?></h4>
                        <small class="text-primary">
                            <i class='bx bx-check-circle'></i> Total: <?= number_format($stats['total_subscriptions'] ?? 0) ?>
                        </small>
                    </div>
                    <div class="text-info">
                        <i class='bx bx-broadcast fs-1'></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Total Employees</h6>
                        <h4 class="mb-0"><?= number_format($stats['total_employees'] ?? 0) ?></h4>
                        <small class="text-success">
                            <i class='bx bx-user-check'></i> <?= number_format($stats['active_employees'] ?? 0) ?> Active
                        </small>
                    </div>
                    <div class="text-warning">
                        <i class='bx bx-id-card fs-1'></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row mb-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Revenue Overview</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Customer Growth</h5>
            </div>
            <div class="card-body">
                <canvas id="customerChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Subscription Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="subscriptionChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payment Status</h5>
            </div>
            <div class="card-body">
                <canvas id="paymentChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Activities</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadMoreActivities()">
            Load More
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>IP Address</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody id="activitiesTable">
                <?php foreach ($activities ?? [] as $activity): ?>
                <tr>
                    <td>
                        <div class="fw-medium"><?= cleanInput($activity['username'] ?? '') ?></div>
                        <small class="text-muted"><?= cleanInput($activity['role'] ?? '') ?></small>
                    </td>
                    <td>
                        <span class="badge bg-<?php 
                            echo match($activity['action'] ?? '') {
                                'create' => 'success',
                                'update' => 'info',
                                'delete' => 'danger',
                                default => 'secondary'
                            };
                        ?>">
                            <?= ucfirst($activity['action'] ?? 'unknown') ?>
                        </span>
                    </td>
                    <td><?= cleanInput($activity['module'] ?? '') ?></td>
                    <td><?= cleanInput($activity['ip_address'] ?? '') ?></td>
                    <td><?= formatDate($activity['created_at'] ?? '', true) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let currentPage = 1;
let currentPeriod = 'month';
let charts = {};

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    refreshCharts();
});

// Initialize all charts
function initializeCharts() {
    // Revenue Chart
    charts.revenue = new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Customer Growth Chart
    charts.customers = new Chart(document.getElementById('customerChart'), {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Subscription Distribution Chart
    charts.subscriptions = new Chart(document.getElementById('subscriptionChart'), {
        type: 'doughnut',
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Payment Status Chart
    charts.payments = new Chart(document.getElementById('paymentChart'), {
        type: 'pie',
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Refresh all charts
function refreshCharts() {
    updateChart('revenue');
    updateChart('customers');
    updateChart('subscriptions');
    updateChart('payments');
}

// Update specific chart
function updateChart(type) {
    fetch(`/admin/dashboard/chart?type=${type}&period=${currentPeriod}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                charts[type].data = data.data;
                charts[type].update();
            }
        })
        .catch(error => {
            console.error(`Error updating ${type} chart:`, error);
        });
}

// Change time period
function changePeriod(period) {
    currentPeriod = period;
    document.getElementById('selectedPeriod').textContent = 
        period.charAt(0).toUpperCase() + period.slice(1);
    refreshCharts();
}

// Load more activities
function loadMoreActivities() {
    currentPage++;
    fetch(`/admin/dashboard/activities?page=${currentPage}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('activitiesTable');
                data.activities.forEach(activity => {
                    tbody.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td>
                                <div class="fw-medium">${cleanInput(activity.username)}</div>
                                <small class="text-muted">${cleanInput(activity.role)}</small>
                            </td>
                            <td>
                                <span class="badge bg-${getBadgeColor(activity.action)}">
                                    ${activity.action.charAt(0).toUpperCase() + activity.action.slice(1)}
                                </span>
                            </td>
                            <td>${cleanInput(activity.module)}</td>
                            <td>${cleanInput(activity.ip_address)}</td>
                            <td>${formatDate(activity.created_at)}</td>
                        </tr>
                    `);
                });

                if (!data.hasMore) {
                    document.querySelector('button[onclick="loadMoreActivities()"]').style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error loading more activities:', error);
        });
}

// Helper function to get badge color
function getBadgeColor(action) {
    return {
        'create': 'success',
        'update': 'info',
        'delete': 'danger'
    }[action] || 'secondary';
}

// Helper function to clean input
function cleanInput(input) {
    if (!input) return '';
    return input.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Helper function to format date
function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleString();
}
</script>
