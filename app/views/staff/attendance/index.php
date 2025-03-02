<?php
$title = 'Attendance Management - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Attendance Management</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if (empty($todayAttendance)): ?>
            <button type="button" class="btn btn-success" id="clockIn">
                <i class="fa fa-sign-in-alt"></i> Clock In
            </button>
        <?php elseif (empty($todayAttendance[0]['time_out'])): ?>
            <button type="button" class="btn btn-warning" id="clockOut">
                <i class="fa fa-sign-out-alt"></i> Clock Out
            </button>
        <?php else: ?>
            <button type="button" class="btn btn-secondary" disabled>
                <i class="fa fa-check"></i> Shift Complete
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Today's Status -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Today's Status</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <h6>Clock In Time</h6>
                    <h4>
                        <?php if (!empty($todayAttendance)): ?>
                            <?= date('h:i A', strtotime($todayAttendance[0]['time_in'])) ?>
                        <?php else: ?>
                            --:--
                        <?php endif; ?>
                    </h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <h6>Clock Out Time</h6>
                    <h4>
                        <?php if (!empty($todayAttendance) && !empty($todayAttendance[0]['time_out'])): ?>
                            <?= date('h:i A', strtotime($todayAttendance[0]['time_out'])) ?>
                        <?php else: ?>
                            --:--
                        <?php endif; ?>
                    </h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <h6>Work Duration</h6>
                    <h4>
                        <?php
                        if (!empty($todayAttendance)) {
                            $timeIn = strtotime($todayAttendance[0]['time_in']);
                            $timeOut = !empty($todayAttendance[0]['time_out']) ? 
                                     strtotime($todayAttendance[0]['time_out']) : 
                                     time();
                            $duration = round(($timeOut - $timeIn) / 3600, 1);
                            echo $duration . ' hrs';
                        } else {
                            echo '0 hrs';
                        }
                        ?>
                    </h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <h6>Status</h6>
                    <h4>
                        <?php if (empty($todayAttendance)): ?>
                            <span class="badge bg-danger">Not Clocked In</span>
                        <?php elseif (empty($todayAttendance[0]['time_out'])): ?>
                            <span class="badge bg-success">On Duty</span>
                        <?php else: ?>
                            <span class="badge bg-info">Shift Complete</span>
                        <?php endif; ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Calendar -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Monthly Attendance</h5>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary" id="prevMonth">
                <i class="fa fa-chevron-left"></i>
            </button>
            <button type="button" class="btn btn-outline-primary" id="currentMonth">
                <?= date('F Y') ?>
            </button>
            <button type="button" class="btn btn-outline-primary" id="nextMonth">
                <i class="fa fa-chevron-right"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered text-center">
                <thead>
                    <tr>
                        <th>Sun</th>
                        <th>Mon</th>
                        <th>Tue</th>
                        <th>Wed</th>
                        <th>Thu</th>
                        <th>Fri</th>
                        <th>Sat</th>
                    </tr>
                </thead>
                <tbody id="calendarBody">
                    <!-- Calendar will be populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Attendance Records -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Attendance Records</h5>
        <button type="button" class="btn btn-primary" id="downloadReport">
            <i class="fa fa-download"></i> Download Report
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $record): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($record['date'])) ?></td>
                            <td><?= date('h:i A', strtotime($record['time_in'])) ?></td>
                            <td>
                                <?= !empty($record['time_out']) ? 
                                    date('h:i A', strtotime($record['time_out'])) : 
                                    '--:--' ?>
                            </td>
                            <td>
                                <?php
                                $timeIn = strtotime($record['time_in']);
                                $timeOut = !empty($record['time_out']) ? 
                                         strtotime($record['time_out']) : 
                                         time();
                                echo round(($timeOut - $timeIn) / 3600, 1) . ' hrs';
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= getStatusBadgeClass($record['status']) ?>">
                                    <?= ucfirst($record['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($record['notes'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Attendance Details Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attendance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="attendanceDetails"></div>
            </div>
        </div>
    </div>
</div>

<?php
function getStatusBadgeClass($status) {
    return match ($status) {
        'present' => 'success',
        'absent' => 'danger',
        'late' => 'warning',
        'leave' => 'info',
        default => 'secondary'
    };
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Clock In/Out Handler
    ['clockIn', 'clockOut'].forEach(id => {
        const button = document.getElementById(id);
        if (button) {
            button.addEventListener('click', function() {
                this.disabled = true;
                
                fetch('/staff/attendance/clock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to record attendance');
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred');
                    this.disabled = false;
                });
            });
        }
    });

    // Calendar Navigation
    let currentDate = new Date();
    
    function updateCalendar(date) {
        const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
        const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
        const startingDay = firstDay.getDay();
        const monthLength = lastDay.getDate();
        
        document.getElementById('currentMonth').textContent = 
            date.toLocaleString('default', { month: 'long', year: 'numeric' });

        const calendarBody = document.getElementById('calendarBody');
        calendarBody.innerHTML = '';

        let day = 1;
        for (let i = 0; i < 6; i++) {
            const row = document.createElement('tr');
            
            for (let j = 0; j < 7; j++) {
                const cell = document.createElement('td');
                
                if (i === 0 && j < startingDay) {
                    cell.textContent = '';
                } else if (day > monthLength) {
                    cell.textContent = '';
                } else {
                    cell.textContent = day;
                    
                    // Highlight current day
                    if (day === new Date().getDate() && 
                        date.getMonth() === new Date().getMonth() && 
                        date.getFullYear() === new Date().getFullYear()) {
                        cell.classList.add('bg-primary', 'text-white');
                    }
                    
                    // Add attendance status
                    const attendanceDate = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const record = <?= json_encode($attendance) ?>.find(r => r.date === attendanceDate);
                    
                    if (record) {
                        cell.classList.add(getStatusClass(record.status));
                        cell.style.cursor = 'pointer';
                        cell.dataset.date = attendanceDate;
                        cell.dataset.record = JSON.stringify(record);
                    }
                    
                    day++;
                }
                
                row.appendChild(cell);
            }
            
            calendarBody.appendChild(row);
            if (day > monthLength) break;
        }
    }

    function getStatusClass(status) {
        return {
            'present': 'bg-success-light',
            'absent': 'bg-danger-light',
            'late': 'bg-warning-light',
            'leave': 'bg-info-light'
        }[status] || '';
    }

    document.getElementById('prevMonth').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        updateCalendar(currentDate);
    });

    document.getElementById('nextMonth').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        updateCalendar(currentDate);
    });

    // Initialize calendar
    updateCalendar(currentDate);

    // Attendance Details Handler
    const attendanceModal = new bootstrap.Modal(document.getElementById('attendanceModal'));
    document.getElementById('calendarBody').addEventListener('click', function(e) {
        const cell = e.target;
        if (cell.tagName === 'TD' && cell.dataset.record) {
            const record = JSON.parse(cell.dataset.record);
            document.getElementById('attendanceDetails').innerHTML = `
                <table class="table table-bordered">
                    <tr>
                        <th width="35%">Date</th>
                        <td>${new Date(record.date).toLocaleDateString()}</td>
                    </tr>
                    <tr>
                        <th>Clock In</th>
                        <td>${new Date(record.time_in).toLocaleTimeString()}</td>
                    </tr>
                    <tr>
                        <th>Clock Out</th>
                        <td>${record.time_out ? new Date(record.time_out).toLocaleTimeString() : '--:--'}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="badge bg-${getStatusBadgeClass(record.status)}">${record.status}</span></td>
                    </tr>
                    <tr>
                        <th>Notes</th>
                        <td>${record.notes || 'No notes'}</td>
                    </tr>
                </table>
            `;
            attendanceModal.show();
        }
    });

    // Download Report Handler
    document.getElementById('downloadReport').addEventListener('click', function() {
        window.location.href = '/staff/attendance/report/download?' + new URLSearchParams({
            month: currentDate.getMonth() + 1,
            year: currentDate.getFullYear()
        });
    });
});
</script>

<style>
.bg-success-light { background-color: rgba(40, 167, 69, 0.2); }
.bg-danger-light { background-color: rgba(220, 53, 69, 0.2); }
.bg-warning-light { background-color: rgba(255, 193, 7, 0.2); }
.bg-info-light { background-color: rgba(23, 162, 184, 0.2); }
</style>
