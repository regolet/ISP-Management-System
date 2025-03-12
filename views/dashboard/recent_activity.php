<?php
function renderDashboardRecentActivity($activities) {
    if (empty($activities)) {
        echo '<p>No recent activity found.</p>';
        return;
    }
?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Activity</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Activity Type</th>
                            <th>Description</th>
                            <th>User</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($activity['type'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($activity['description'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($activity['user_name'] ?? ''); ?></td>
                            <td><?php echo format_datetime($activity['created_at'] ?? '', 'M d, Y H:i'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php
}
?>
