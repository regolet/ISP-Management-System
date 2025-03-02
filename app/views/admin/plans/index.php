<?php
$title = 'Internet Plans - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Internet Plans</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPlanModal">
            <i class="fa fa-plus"></i> Add Plan
        </button>
    </div>
</div>

<!-- Plans Table -->
<div class="card">
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
                    <?php foreach ($plans as $plan): ?>
                    <tr>
                        <td><?= htmlspecialchars($plan['name']) ?></td>
                        <td><?= htmlspecialchars($plan['description']) ?></td>
                        <td><?= formatBandwidth($plan['bandwidth']) ?></td>
                        <td><?= formatCurrency($plan['amount']) ?></td>
                        <td>
                            <span class="badge bg-info">
                                <?= $plan['subscriber_count'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $plan['status'] === 'active' ? 'success' : 'danger' ?>">
                                <?= ucfirst($plan['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-info" 
                                        onclick="viewPlan(<?= $plan['id'] ?>)">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-primary" 
                                        onclick="editPlan(<?= htmlspecialchars(json_encode($plan)) ?>)">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-sm btn-<?= $plan['status'] === 'active' ? 'danger' : 'success' ?>" 
                                        onclick="togglePlanStatus(<?= $plan['id'] ?>, '<?= $plan['status'] ?>')">
                                    <i class="fa fa-power-off"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Plan Modal -->
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="planForm" method="POST" action="/admin/plans/save">
                <?= csrf_field() ?>
                <input type="hidden" id="plan_id" name="id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add New Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
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
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   step="0.01" required>
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
    // Find plan data from the table
    const row = event.target.closest('tr');
    const plan = {
        id: id,
        name: row.cells[0].textContent,
        description: row.cells[1].textContent,
        bandwidth: row.cells[2].textContent.replace(' Mbps', ''),
        amount: row.cells[3].textContent.replace('$', '').replace(',', ''),
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
    document.getElementById('amount').value = plan.amount.replace('$', '').replace(',', '');
    
    document.querySelector('#planModal .modal-title').textContent = plan.id ? 'Edit Plan' : 'Add New Plan';
    new bootstrap.Modal(document.getElementById('planModal')).show();
}

function togglePlanStatus(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = currentStatus === 'active' ? 'deactivate' : 'activate';
    
    if (confirm(`Are you sure you want to ${action} this plan?`)) {
        fetch(`/admin/plans/${id}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to update plan status');
            }
        });
    }
}

// Initialize tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
});
</script>
