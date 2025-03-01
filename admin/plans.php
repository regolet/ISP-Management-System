<?php
require_once '../config.php';
check_auth();
$page_title = 'Plans';
$_SESSION['active_menu'] = 'plans';
include 'header.php';
include 'navbar.php';

// Add this query before using $result
$query = "SELECT p.*, 
         (SELECT COUNT(*) FROM subscriptions WHERE plan_id = p.id AND status = 'active') as subscriber_count 
         FROM plans p 
         ORDER BY p.name";
$result = $conn->query($query);
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Internet Plans</h1>
    </div>

    <!-- Plans Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Plan Name</th>
                            <th>Description</th>
                            <th>Bandwidth</th>
                            <th>Monthly Fee</th>
                            <th>Subscribers</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($plan = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($plan['name']); ?></td>
                            <td><?php echo htmlspecialchars($plan['description']); ?></td>
                            <td><?php echo htmlspecialchars($plan['bandwidth']); ?> Mbps</td>
                            <td>₱<?php echo number_format($plan['amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo $plan['subscriber_count']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $plan['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($plan['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="viewPlan(<?php echo $plan['id']; ?>)">
                                        <i class='bx bx-show'></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            onclick="editPlan(<?php echo htmlspecialchars(json_encode($plan)); ?>)">
                                        <i class='bx bx-edit-alt'></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-<?php echo $plan['status'] === 'active' ? 'danger' : 'success'; ?>" 
                                            onclick="togglePlanStatus(<?php echo $plan['id']; ?>, '<?php echo $plan['status']; ?>')">
                                        <i class='bx bx-power-off'></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Plan Button -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1000;">
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow" 
                data-bs-toggle="modal" 
                data-bs-target="#addPlanModal">
            <i class='bx bx-plus fs-5'></i>
            <span>Add Plan</span>
        </button>
    </div>
</div>

<!-- Add/Edit Plan Modal -->
<div class="modal fade" id="addPlanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="planForm" method="POST" action="plan_save.php">
                <div class="modal-body">
                    <input type="hidden" id="plan_id" name="id">
                    <div class="mb-3">
                        <label for="name" class="form-label">Plan Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="bandwidth" class="form-label">Bandwidth (Mbps)</label>
                        <input type="number" class="form-control" id="bandwidth" name="bandwidth" required>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Monthly Fee</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewPlan(id) {
    // Fetch plan details from the table row
    const row = event.target.closest('tr');
    const plan = {
        id: id,
        name: row.cells[0].textContent,
        description: row.cells[1].textContent,
        bandwidth: row.cells[2].textContent.replace(' Mbps', ''),
        amount: row.cells[3].textContent.replace('₱', '').replace(',', ''),
        subscriber_count: row.cells[4].querySelector('.badge').textContent.trim(),
        status: row.cells[5].querySelector('.badge').textContent.trim()
    };
    
    // Store plan data for edit functionality
    window.currentPlan = plan;
    
    // Open edit modal with current plan data
    editPlan(plan);
}

function editPlan(plan) {
    document.getElementById('plan_id').value = plan.id;
    document.getElementById('name').value = plan.name;
    document.getElementById('description').value = plan.description;
    document.getElementById('bandwidth').value = plan.bandwidth.replace(' Mbps', '');
    document.getElementById('amount').value = plan.amount.replace('₱', '').replace(',', '');
    
    document.querySelector('#addPlanModal .modal-title').textContent = 'Edit Plan';
    new bootstrap.Modal(document.getElementById('addPlanModal')).show();
}

function togglePlanStatus(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = currentStatus === 'active' ? 'deactivate' : 'activate';
    
    if (confirm(`Are you sure you want to ${action} this plan?`)) {
        window.location.href = `plan_status.php?id=${id}&status=${newStatus}`;
    }
}
</script>

<?php include 'footer.php'; ?>
