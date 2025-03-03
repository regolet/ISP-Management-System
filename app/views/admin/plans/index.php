<?php
$title = 'Manage Plans - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class='bx bx-package'></i> Manage Plans</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/plans/create" class="btn btn-outline-primary">
            <i class='bx bx-plus'></i> Add Plan
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($plans)): ?>
            <div class="alert alert-info">
                No plans found. Click the "Add Plan" button to create one.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Bandwidth</th>
                            <th>Amount</th>
                            <th>Subscribers</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plans as $plan): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($plan['name']) ?>
                                    <?php if (!empty($plan['description'])): ?>
                                        <small class="text-muted d-block"><?= htmlspecialchars($plan['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= formatBandwidth($plan['bandwidth']) ?></td>
                                <td><?= formatCurrency($plan['amount']) ?></td>
                                <td><?= number_format($plan['subscribers']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $plan['status'] === 'active' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($plan['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/plans/edit/<?= $plan['id'] ?>" 
                                           class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Edit Plan">
                                            <i class='bx bx-edit'></i>
                                        </a>
                                        <?php if ($plan['subscribers'] == 0): ?>
                                            <button type="button" class="btn btn-outline-danger delete-plan" 
                                                    data-id="<?= $plan['id'] ?>" data-bs-toggle="tooltip" title="Delete Plan">
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
        <?php endif; ?>
    </div>
</div>

<?php
function formatBandwidth($speed) {
    if ($speed >= 1000) {
        return ($speed / 1000) . ' Gbps';
    }
    return $speed . ' Mbps';
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete Plan
    document.querySelectorAll('.delete-plan').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this plan? This action cannot be undone.')) {
                const planId = this.dataset.id;
                
                fetch(`/admin/plans/${planId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-Token': '<?= csrf_token() ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to delete plan');
                    }
                });
            }
        });
    });

    // Initialize Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
