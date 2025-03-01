<?php
require_once '../config.php';
check_auth();

$page_title = 'Roles Management';
$_SESSION['active_menu'] = 'roles';

// Get all roles with their permissions and user count
$roles_query = "
    SELECT r.*, 
           COUNT(DISTINCT rp.permission_id) as permission_count,
           COUNT(DISTINCT u.id) as user_count
    FROM roles r
    LEFT JOIN role_permissions rp ON r.id = rp.role_id
    LEFT JOIN users u ON u.role = r.name
    GROUP BY r.id
    ORDER BY r.name
";
$roles = $conn->query($roles_query);

// Get all permissions grouped by category
$permissions_query = "SELECT * FROM permissions ORDER BY category, name";
$permissions_result = $conn->query($permissions_query);

$permissions_by_category = [];
while ($permission = $permissions_result->fetch_assoc()) {
    $permissions_by_category[$permission['category']][] = $permission;
}

include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Roles Management</h1>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" 
                data-bs-toggle="modal" data-bs-target="#roleModal" onclick="resetRoleForm()">
            <i class="bx bx-plus"></i>
            <span>Add Role</span>
        </button>
    </div>

    <!-- Roles Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Permissions</th>
                            <th>Users</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($role = $roles->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($role['name']); ?></td>
                                <td><?php echo htmlspecialchars($role['description']); ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $role['permission_count']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo $role['user_count']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($role['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info" 
                                                onclick="viewRole(<?php echo $role['id']; ?>)">
                                            <i class="bx bx-show"></i>
                                        </button>
                                        <?php if ($role['name'] !== 'admin'): ?>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="editRole(<?php echo $role['id']; ?>)">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            <?php if ($role['user_count'] == 0): ?>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="deleteRole(<?php echo $role['id']; ?>)">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="roleForm" method="POST" action="role_save.php">
                <input type="hidden" name="role_id" id="role_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input type="text" class="form-control" name="name" id="role_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="role_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <?php foreach ($permissions_by_category as $category => $permissions): ?>
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><?php echo ucwords($category); ?></h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <?php foreach ($permissions as $permission): ?>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="permissions[]" 
                                                           value="<?php echo $permission['id']; ?>"
                                                           id="perm_<?php echo $permission['id']; ?>">
                                                    <label class="form-check-label" 
                                                           for="perm_<?php echo $permission['id']; ?>">
                                                        <?php echo ucwords(str_replace('_', ' ', $permission['name'])); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetRoleForm() {
    document.getElementById('roleForm').reset();
    document.getElementById('role_id').value = '';
    document.querySelector('#roleModal .modal-title').textContent = 'Add New Role';
}

function editRole(id) {
    fetch(`role_get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('role_id').value = data.id;
            document.getElementById('role_name').value = data.name;
            document.getElementById('role_description').value = data.description;
            
            // Reset all checkboxes
            document.querySelectorAll('input[name="permissions[]"]')
                .forEach(checkbox => checkbox.checked = false);
            
            // Check permissions
            data.permissions.forEach(permissionId => {
                const checkbox = document.getElementById(`perm_${permissionId}`);
                if (checkbox) checkbox.checked = true;
            });
            
            document.querySelector('#roleModal .modal-title').textContent = 'Edit Role';
            new bootstrap.Modal(document.getElementById('roleModal')).show();
        });
}

function deleteRole(id) {
    if (confirm('Are you sure you want to delete this role? This action cannot be undone.')) {
        window.location.href = `role_delete.php?id=${id}`;
    }
}

function viewRole(id) {
    window.location.href = `role_view.php?id=${id}`;
}
</script>

<?php include 'footer.php'; ?>
