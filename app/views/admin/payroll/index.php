<?php
$title = 'Payroll Management - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Payroll Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/payroll/report" class="btn btn-info">
                <i class="fa fa-chart-bar"></i> View Report
            </a>
            <a href="/admin/payroll/create" class="btn btn-primary">
                <i class="fa fa-plus"></i> Create Payroll
            </a>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/payroll" class="row g-3">
            <div class="col-md-3">
                <label for="month" class="form-label">Month</label>
                <select class="form-select" id="month" name="month">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= ($filters['month'] ?? date('n')) == $i ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="year" class="form-label">Year</label>
                <select class="form-select" id="year" name="year">
                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                        <option value="<?= $i ?>" <?= ($filters['year'] ?? date('Y')) == $i ? 'selected' : '' ?>>
                            <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>
                        Draft
                    </option>
                    <option value="processing" <?= ($filters['status'] ?? '') === 'processing' ? 'selected' : '' ?>>
                        Processing
                    </option>
                    <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>
                        Approved
                    </option>
                    <option value="paid" <?= ($filters['status'] ?? '') === 'paid' ? 'selected' : '' ?>>
                        Paid
                    </option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Basic Pay</h6>
                <h3 class="mb-0"><?= formatCurrency($summary['total_basic']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Deductions</h6>
                <h3 class="mb-0"><?= formatCurrency($summary['total_deductions']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Net Pay</h6>
                <h3 class="mb-0"><?= formatCurrency($summary['total_net']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Employees</h6>
                <h3 class="mb-0"><?= $summary['total_employees'] ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Payroll Periods -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Pay Date</th>
                        <th>Employees</th>
                        <th>Total Basic</th>
                        <th>Total Deductions</th>
                        <th>Total Net</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($periods)): ?>
                        <?php foreach ($periods as $period): ?>
                            <tr>
                                <td>
                                    <div><?= date('F d, Y', strtotime($period['period_start'])) ?></div>
                                    <small class="text-muted">
                                        to <?= date('F d, Y', strtotime($period['period_end'])) ?>
                                    </small>
                                </td>
                                <td><?= date('F d, Y', strtotime($period['pay_date'])) ?></td>
                                <td class="text-center"><?= $period['employee_count'] ?></td>
                                <td class="text-end"><?= formatCurrency($period['total_basic']) ?></td>
                                <td class="text-end"><?= formatCurrency($period['total_deductions']) ?></td>
                                <td class="text-end"><?= formatCurrency($period['total_net']) ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($period['status']) {
                                            'paid' => 'success',
                                            'approved' => 'info',
                                            'processing' => 'warning',
                                            'draft' => 'secondary',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?= ucfirst($period['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/payroll/<?= $period['id'] ?>" 
                                           class="btn btn-info" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <?php if ($period['status'] === 'draft'): ?>
                                            <a href="/admin/payroll/<?= $period['id'] ?>/edit" 
                                               class="btn btn-primary" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger" 
                                                    onclick="deletePeriod(<?= $period['id'] ?>)" 
                                                    title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        <?php elseif ($period['status'] === 'processing'): ?>
                                            <button type="button" class="btn btn-success" 
                                                    onclick="approvePeriod(<?= $period['id'] ?>)" 
                                                    title="Approve">
                                                <i class="fa fa-check"></i>
                                            </button>
                                        <?php elseif ($period['status'] === 'approved'): ?>
                                            <button type="button" class="btn btn-success" 
                                                    onclick="markAsPaid(<?= $period['id'] ?>)" 
                                                    title="Mark as Paid">
                                                <i class="fa fa-money-bill"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fa fa-info-circle text-muted mb-2"></i>
                                <p class="text-muted mb-0">No payroll periods found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete payroll period
    window.deletePeriod = function(id) {
        if (confirm('Are you sure you want to delete this payroll period? This action cannot be undone.')) {
            fetch(`/admin/payroll/${id}`, {
                method: 'DELETE',
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
                    alert(data.error || 'Failed to delete payroll period');
                }
            });
        }
    };

    // Approve payroll period
    window.approvePeriod = function(id) {
        if (confirm('Are you sure you want to approve this payroll period?')) {
            fetch(`/admin/payroll/${id}/approve`, {
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
                    alert(data.error || 'Failed to approve payroll period');
                }
            });
        }
    };

    // Mark payroll as paid
    window.markAsPaid = function(id) {
        if (confirm('Are you sure you want to mark this payroll period as paid?')) {
            fetch(`/admin/payroll/${id}/paid`, {
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
                    alert(data.error || 'Failed to mark payroll period as paid');
                }
            });
        }
    };

    // Filter change handlers
    ['month', 'year', 'status'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            document.querySelector('form').submit();
        });
    });
});
</script>
