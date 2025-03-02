<?php
$title = 'Employee Management - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Employee Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/employees/create" class="btn btn-primary">
            <i class="fa fa-plus"></i> Add Employee
        </a>
    </div>
</div>

<!-- Search Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/employees" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search employees..." 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $key => $name): ?>
                        <option value="<?= $key ?>" 
                                <?= ($filters['department'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>
                        Active
                    </option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                        Inactive
                    </option>
                </select>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>
            
            <div class="col-md-2">
                <button type="button" class="btn btn-success w-100" id="exportEmployees">
                    <i class="fa fa-download"></i> Export
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Employees Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Contact</th>
                        <th>Daily Rate</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($employees['employees'])): ?>
                        <?php foreach ($employees['employees'] as $emp): ?>
                            <tr>
                                <td>
                                    <div><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($emp['employee_code']) ?>
                                    </small>
                                </td>
                                <td><?= htmlspecialchars($emp['position']) ?></td>
                                <td><?= htmlspecialchars($emp['department']) ?></td>
                                <td>
                                    <div><?= htmlspecialchars($emp['email']) ?></div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($emp['phone']) ?>
                                    </small>
                                </td>
                                <td><?= formatCurrency($emp['daily_rate']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $emp['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($emp['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="/admin/employees/<?= $emp['id'] ?>" 
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="/admin/employees/<?= $emp['id'] ?>/edit" 
                                           class="btn btn-sm btn-primary" title="Edit Employee">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <?php if (!$emp['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    onclick="createUserAccount(<?= $emp['id'] ?>)" 
                                                    title="Create User Account">
                                                <i class="fa fa-user-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($emp['status'] === 'active'): ?>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deactivateEmployee(<?= $emp['id'] ?>)" 
                                                    title="Deactivate">
                                                <i class="fa fa-user-times"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    onclick="activateEmployee(<?= $emp['id'] ?>)" 
                                                    title="Activate">
                                                <i class="fa fa-user-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fa fa-info-circle text-muted mb-2"></i>
                                <p class="text-muted mb-0">No employees found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($employees['pages'] > 1): ?>
    <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $employees['pages']; $i++): ?>
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $queryString ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
<?php endif; ?>

<script>
// Export employees
document.getElementById('exportEmployees').addEventListener('click', function() {
    const form = document.querySelector('form');
    const params = new URLSearchParams(new FormData(form));
    window.location.href = '/admin/employees/export?' + params.toString();
});

// Employee status actions
function deactivateEmployee(id) {
    updateEmployeeStatus(id, 'inactive', 'deactivate');
}

function activateEmployee(id) {
    updateEmployeeStatus(id, 'active', 'activate');
}

function updateEmployeeStatus(id, status, action) {
    if (confirm(`Are you sure you want to ${action} this employee?`)) {
        fetch(`/admin/employees/${id}/status`, {
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
                alert(data.error || `Failed to ${action} employee`);
            }
        });
    }
}

// Create user account
function createUserAccount(id) {
    if (confirm('Are you sure you want to create a user account for this employee?')) {
        fetch(`/admin/employees/${id}/create-account`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Account created successfully!\n\nUsername: ${data.username}\nPassword: ${data.password}\n\nPlease save these credentials.`);
                location.reload();
            } else {
                alert(data.error || 'Failed to create user account');
            }
        });
    }
}

// Initialize tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
});
</script>
