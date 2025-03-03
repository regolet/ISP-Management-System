<?php
$title = 'Audit Logs - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class='bx bx-history'></i> Audit Logs</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <button type="button" class="btn btn-danger" onclick="cleanLogs()">
                <i class='bx bx-trash'></i> Clean Old Logs
            </button>
            <a href="/admin/audit/export<?= !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ?>" 
               class="btn btn-primary">
                <i class='bx bx-export'></i> Export Logs
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <!-- Settings Navigation -->
        <div class="card mb-4">
            <div class="list-group list-group-flush">
                <a href="/admin/settings/general" class="list-group-item list-group-item-action">
                    <i class='bx bx-cog'></i> General Settings
                </a>
                <a href="/admin/settings/roles" class="list-group-item list-group-item-action">
                    <i class='bx bx-shield'></i> Roles & Permissions
                </a>
                <a href="/admin/backup" class="list-group-item list-group-item-action">
                    <i class='bx bx-data'></i> Backup Management
                </a>
                <a href="/admin/audit" class="list-group-item list-group-item-action active">
                    <i class='bx bx-history'></i> Audit Logs
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="/admin/audit">
                    <div class="mb-3">
                        <label class="form-label">User</label>
                        <select class="form-select" name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" 
                                        <?= ($filters['user_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Module</label>
                        <select class="form-select" name="module">
                            <option value="">All Modules</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?= $module ?>" 
                                        <?= ($filters['module'] ?? '') === $module ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($module) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Action</label>
                        <select class="form-select" name="action">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?= $action ?>" 
                                        <?= ($filters['action'] ?? '') === $action ? 'selected' : '' ?>>
                                    <?= ucfirst(htmlspecialchars($action)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <select class="form-select" name="date_range">
                            <option value="">All Time</option>
                            <option value="today" <?= ($filters['date_range'] ?? '') === 'today' ? 'selected' : '' ?>>
                                Today
                            </option>
                            <option value="week" <?= ($filters['date_range'] ?? '') === 'week' ? 'selected' : '' ?>>
                                This Week
                            </option>
                            <option value="month" <?= ($filters['date_range'] ?? '') === 'month' ? 'selected' : '' ?>>
                                This Month
                            </option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class='bx bx-filter'></i> Apply Filters
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <!-- Audit Logs -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Module</th>
                                <th>IP Address</th>
                                <th>Date & Time</th>
                                <th class="text-end">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($logs['logs'])): ?>
                                <?php foreach ($logs['logs'] as $log): ?>
                                <tr>
                                    <td>
                                        <div class="fw-medium"><?= htmlspecialchars($log['username']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($log['role']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($log['action']) {
                                                'create' => 'success',
                                                'update' => 'info',
                                                'delete' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?= ucfirst($log['action']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['module']) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($log['ip_address']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars(substr($log['user_agent'], 0, 50)) ?>...</small>
                                    </td>
                                    <td><?= formatDate($log['created_at'], true) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-info" 
                                                onclick="viewDetails(<?= $log['id'] ?>)">
                                            <i class='bx bx-info-circle'></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class='bx bx-info-circle text-muted fs-1'></i>
                                        <p class="text-muted mb-0">No audit logs found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($logs['pages'] > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $logs['pages']; $i++): ?>
                            <li class="page-item <?= $current_page === $i ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $queryString ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Old Values</h6>
                        <pre class="old-values bg-light p-3 rounded"></pre>
                    </div>
                    <div class="col-md-6">
                        <h6>New Values</h6>
                        <pre class="new-values bg-light p-3 rounded"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// View log details
function viewDetails(id) {
    fetch(`/admin/audit/${id}`)
        .then(response => response.json())
        .then(data => {
            const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
            document.querySelector('.old-values').textContent = 
                data.old_values ? JSON.stringify(JSON.parse(data.old_values), null, 2) : 'No data';
            document.querySelector('.new-values').textContent = 
                data.new_values ? JSON.stringify(JSON.parse(data.new_values), null, 2) : 'No data';
            modal.show();
        });
}

// Clean old logs
function cleanLogs() {
    if (confirm('Are you sure you want to clean old logs? This will delete logs older than 90 days.')) {
        fetch('/admin/audit/clean', {
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
                alert(data.error || 'Failed to clean logs');
            }
        });
    }
}

// Auto-submit form on filter change
document.querySelectorAll('select[name]').forEach(select => {
    select.addEventListener('change', () => {
        select.closest('form').submit();
    });
});
</script>
