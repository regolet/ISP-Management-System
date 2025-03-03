<?php
$title = 'Time Action - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Time Action</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/staff/attendance/view" class="btn btn-secondary">
            <i class="fa fa-calendar"></i> View Attendance
        </a>
    </div>
</div>

<!-- Current Time Display -->
<div class="row mb-4">
    <div class="col-md-6 mx-auto text-center">
        <div class="card">
            <div class="card-body">
                <h3 class="mb-3">Current Time</h3>
                <div class="display-4 mb-3" id="currentTime">00:00:00</div>
                <div class="h5 text-muted" id="currentDate"></div>
            </div>
        </div>
    </div>
</div>

<!-- Time Action Card -->
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <!-- Today's Status -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6>Today's Status</h6>
                            <?php if (empty($todayAttendance)): ?>
                                <span class="badge bg-warning">Not Clocked In</span>
                            <?php else: ?>
                                <span class="badge bg-<?= getStatusBadgeClass($todayAttendance['status']) ?>">
                                    <?= ucfirst($todayAttendance['status']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <h6>Current Shift</h6>
                            <?php if ($currentShift): ?>
                                <div>
                                    <?= htmlspecialchars($currentShift['name']) ?>
                                    <small class="text-muted d-block">
                                        <?= formatTime($currentShift['start_time']) ?> - 
                                        <?= formatTime($currentShift['end_time']) ?>
                                    </small>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">No active shift</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Time Records -->
                <?php if (!empty($todayAttendance)): ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6>Time In</h6>
                                <div class="h4">
                                    <?= formatDateTime($todayAttendance['time_in']) ?>
                                </div>
                                <?php if ($todayAttendance['late_minutes'] > 0): ?>
                                    <small class="text-danger">
                                        <?= $todayAttendance['late_minutes'] ?> minutes late
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6>Time Out</h6>
                                <?php if ($todayAttendance['time_out']): ?>
                                    <div class="h4">
                                        <?= formatDateTime($todayAttendance['time_out']) ?>
                                    </div>
                                    <?php if ($todayAttendance['overtime_minutes'] > 0): ?>
                                        <small class="text-success">
                                            <?= $todayAttendance['overtime_minutes'] ?> minutes overtime
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Not clocked out</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="text-center">
                    <?php if (empty($todayAttendance)): ?>
                        <button type="button" class="btn btn-success btn-lg time-action" data-action="in">
                            <i class="fa fa-sign-in-alt"></i> Clock In
                        </button>
                    <?php elseif (empty($todayAttendance['time_out'])): ?>
                        <button type="button" class="btn btn-warning btn-lg time-action" data-action="out">
                            <i class="fa fa-sign-out-alt"></i> Clock Out
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-lg" disabled>
                            <i class="fa fa-check"></i> Shift Complete
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Notes Form -->
                <?php if (empty($todayAttendance) || empty($todayAttendance['time_out'])): ?>
                    <div class="mt-4">
                        <form id="notesForm" class="needs-validation" novalidate>
                            <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                                <div class="form-text">
                                    Add any relevant notes about your attendance (e.g., reason for late arrival)
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
function formatDateTime($datetime) {
    return date('h:i:s A', strtotime($datetime));
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function getStatusBadgeClass($status) {
    return match ($status) {
        'present' => 'success',
        'late' => 'warning',
        'absent' => 'danger',
        default => 'secondary'
    };
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update current time
    function updateTime() {
        const now = new Date();
        document.getElementById('currentTime').textContent = 
            now.toLocaleTimeString('en-US', { 
                hour12: true,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        document.getElementById('currentDate').textContent = 
            now.toLocaleDateString('en-US', { 
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
    }

    updateTime();
    setInterval(updateTime, 1000);

    // Time Action Handler
    document.querySelectorAll('.time-action').forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.action;
            const notes = document.getElementById('notes').value;

            // Confirm action
            if (!confirm(`Are you sure you want to clock ${action}?`)) {
                return;
            }

            // Disable button
            this.disabled = true;
            
            fetch('/staff/attendance/time-action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                },
                body: JSON.stringify({
                    action: action,
                    notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || `Failed to clock ${action}`);
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
                this.disabled = false;
            });
        });
    });

    // Geolocation Check (if required)
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                // Store coordinates in hidden fields if needed
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                // You can add hidden fields to the form or send these with the time action
            },
            error => {
                console.warn('Geolocation error:', error);
                // Handle geolocation error if needed
            }
        );
    }
});
</script>

<style>
#currentTime {
    font-family: monospace;
    font-size: 3rem;
    font-weight: bold;
    color: #333;
}

.time-action {
    min-width: 200px;
    padding: 1rem 2rem;
}

@media (max-width: 768px) {
    #currentTime {
        font-size: 2rem;
    }
}
</style>
