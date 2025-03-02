<?php
$title = 'Attendance Records - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Attendance Records</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/staff/attendance/time-action" class="btn btn-primary">
                <i class="fa fa-clock"></i> Time Action
            </a>
            <button type="button" class="btn btn-info" id="downloadReport">
                <i class="fa fa-download"></i> Download Report
            </button>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Present Days</h5>
                <h3 class="card-text"><?= $summary['present_days'] ?? 0 ?></h3>
                <p class="mb-0">This Month</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Late Days</h5>
                <h3 class="card-text"><?= $summary['late_days'] ?? 0 ?></h3>
                <p class="mb-0">Total <?= formatMinutes($summary['total_late_minutes'] ?? 0) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Overtime</h5>
                <h3 class="card-text"><?= formatMinutes($summary['total_overtime_minutes'] ?? 0) ?></h3>
                <p class="mb-0">This Month</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5 class="card-title">Absent Days</h5>
                <h3 class="card-text"><?= $summary['absent_days'] ?? 0 ?></h3>
                <p class="mb-0">This Month</p>
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
    <div class="card-header">
        <h5 class="mb-0">Attendance History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Shift</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Late</th>
                        <th>Overtime</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $record): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($record['date'])) ?></td>
                            <td>
                                <?php if ($record['shift_name']): ?>
                                    <?= htmlspecialchars($record['shift_name']) ?>
                                    <small class="text-muted d-block">
                                        <?= formatTime($record['shift_start']) ?> - 
                                        <?= formatTime($record['shift_end']) ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= formatDateTime($record['time_in']) ?></td>
                            <td>
                                <?= $record['time_out'] ? formatDateTime($record['time_out']) : '-' ?>
                            </td>
                            <td>
                                <?php if ($record['late_minutes'] > 0): ?>
                                    <span class="text-danger">
                                        <?= formatMinutes($record['late_minutes']) ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($record['overtime_minutes'] > 0): ?>
                                    <span class="text-success">
                                        <?= formatMinutes($record['overtime_minutes']) ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= getStatusBadgeClass($record['status']) ?>">
                                    <?= ucfirst($record['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info view-details" 
                                        data-id="<?= $record['id'] ?>" title="View Details">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </td>
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
            <div class="modal-body" id="attendanceDetails">
                <!-- Details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<?php
function formatDateTime($datetime) {
    return $datetime ? date('h:i:s A', strtotime($datetime)) : '-';
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function formatMinutes($minutes) {
    if ($minutes >= 60) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return "{$hours}h {$mins}m";
    }
    return "{$minutes}m";
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
                cell.style.height = '100px';
                cell.style.verticalAlign = 'top';
                
                if (i === 0 && j < startingDay) {
                    cell.textContent = '';
                } else if (day > monthLength) {
                    cell.textContent = '';
                } else {
                    const currentDay = new Date(date.getFullYear(), date.getMonth(), day);
                    const attendance = getAttendanceForDate(currentDay);
                    
                    cell.innerHTML = `
                        <div class="d-flex justify-content-between">
                            <span>${day}</span>
                            ${attendance ? `
                                <span class="badge bg-${getStatusBadgeClass(attendance.status)}">
                                    ${attendance.status}
                                </span>
                            ` : ''}
                        </div>
                        ${attendance ? `
                            <div class="small text-muted mt-1">
                                In: ${formatTime(attendance.time_in)}<br>
                                ${attendance.time_out ? `Out: ${formatTime(attendance.time_out)}` : ''}
                            </div>
                        ` : ''}
                    `;
                    
                    if (isToday(currentDay)) {
                        cell.classList.add('bg-light');
                    }
                    
                    day++;
                }
                
                row.appendChild(cell);
            }
            
            calendarBody.appendChild(row);
            if (day > monthLength) break;
        }
    }

    function getAttendanceForDate(date) {
        // This should match with your attendance data structure
        const dateStr = date.toISOString().split('T')[0];
        return attendanceData[dateStr];
    }

    function isToday(date) {
        const today = new Date();
        return date.getDate() === today.getDate() &&
               date.getMonth() === today.getMonth() &&
               date.getFullYear() === today.getFullYear();
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

    // View Details Handler
    const attendanceModal = new bootstrap.Modal(document.getElementById('attendanceModal'));
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function() {
            const attendanceId = this.dataset.id;
            
            fetch(`/staff/attendance/${attendanceId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('attendanceDetails').innerHTML = `
                        <table class="table table-bordered">
                            <tr>
                                <th width="35%">Date</th>
                                <td>${new Date(data.date).toLocaleDateString()}</td>
                            </tr>
                            <tr>
                                <th>Shift</th>
                                <td>
                                    ${data.shift_name}<br>
                                    <small class="text-muted">
                                        ${formatTime(data.shift_start)} - ${formatTime(data.shift_end)}
                                    </small>
                                </td>
                            </tr>
                            <tr>
                                <th>Time In</th>
                                <td>${formatDateTime(data.time_in)}</td>
                            </tr>
                            <tr>
                                <th>Time Out</th>
                                <td>${data.time_out ? formatDateTime(data.time_out) : '-'}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge bg-${getStatusBadgeClass(data.status)}">
                                        ${data.status}
                                    </span>
                                </td>
                            </tr>
                            ${data.late_minutes > 0 ? `
                                <tr>
                                    <th>Late</th>
                                    <td class="text-danger">${formatMinutes(data.late_minutes)}</td>
                                </tr>
                            ` : ''}
                            ${data.overtime_minutes > 0 ? `
                                <tr>
                                    <th>Overtime</th>
                                    <td class="text-success">${formatMinutes(data.overtime_minutes)}</td>
                                </tr>
                            ` : ''}
                            ${data.notes ? `
                                <tr>
                                    <th>Notes</th>
                                    <td>${data.notes}</td>
                                </tr>
                            ` : ''}
                        </table>
                    `;
                    attendanceModal.show();
                });
        });
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
.calendar-cell {
    height: 100px;
    vertical-align: top;
    padding: 5px;
}

.calendar-event {
    font-size: 0.8rem;
    margin-top: 2px;
}
</style>
