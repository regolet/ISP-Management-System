<?php
$title = 'Roles & Permissions - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class='bx bx-shield'></i> Roles & Permissions</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
            <i class='bx bx-plus'></i> Create Role
        </button>
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
                <a href="/admin/settings/roles" class="list-group-item list-group-item-action active">
                    <i class='bx bx-shield'></i> Roles & Permissions
                </a>
                <a href="/admin/backup" class="list-group-item list-group-item-action">
                    <i class='bx bx-data'></i> Backup Management
                </a>
                <a href="/admin/audit" class="list-group-item list-group-item-action">
                    <i class='bx bx-history'></i> Audit Logs
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <!-- Roles List -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th>Description</th>
                                <th class="text-center">Users</th>
                                <th class="text-center">Permissions</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                            <tr>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($role['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($role['slug']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($role['description']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= $role['user_count'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?= $role['permission_count'] ?? 0 ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info" 
                                                onclick="editPermissions(<?= $role['id'] ?>)" 
                                                title="Edit Permissions">
                                            <i class='bx bx-key'></i>
                                        </button>
                                        <?php if (!$role['is_system']): ?>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="deleteRole(<?= $role['id'] ?>)" 
                                                title="Delete Role">
                                            <i class='bx bx-trash'></i>
                                        </button>
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
    </div>
</div>

<!-- Create Role Modal -->
<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/admin/settings/roles/create">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" class="form-control" name="slug" required>
                        <div class="form-text">Unique identifier for the role (e.g., "admin", "staff")</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Permissions Modal -->
<div class="modal fade" id="editPermissionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="permissionsForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Role Permissions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php foreach ($permissions as $module => $modulePermissions): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><?= htmlspecialchars($module) ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($modulePermissions as $permission): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" 
                                               name="permissions[]" value="<?= $permission['id'] ?>"
                                               id="perm_<?= $permission['id'] ?>">
                                        <label class="form-check-label" for="perm_<?= $permission['id'] ?>">
                                            <?= htmlspecialchars($permission['name']) ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Permissions</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle role deletion
function deleteRole(id) {
    if (confirm('Are you sure you want to delete this role? This action cannot be undone.')) {
        fetch(`/admin/settings/roles/${id}`, {
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
                alert(data.error || 'Failed to delete role');
            }
        });
    }
}

// Handle permission editing
function editPermissions(id) {
    const modal = new bootstrap.Modal(document.getElementById('editPermissionsModal'));
    const form = document.getElementById('permissionsForm');
    
    // Reset form
    form.reset();
    form.action = `/admin/settings/roles/${id}/permissions`;
    
    // Get current permissions
    fetch(`/admin/settings/roles/${id}`)
        .then(response => response.json())
        .then(data => {
            // Check permissions
            data.permissions.forEach(permId => {
                const checkbox = document.getElementById(`perm_${permId}`);
                if (checkbox) checkbox.checked = true;
            });
            modal.show();
        });
}

// Auto-generate slug from name
document.querySelector('input[name="name"]').addEventListener('input', function(e) {
    const slug = e.target.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
    document.querySelector('input[name="slug"]').value = slug;
});
</script>
