<?php
$title = 'Attendance Report - Admin Panel';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Attendance Report</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <a href="/admin/attendance" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Attendance
            </a>
            <button type="button" class="btn btn-success" id="exportReport">
                <i class="fa fa-download"></i> Export Report
            </button>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/admin/attendance/report" class="row g-3">
            <div class="col-md-3">
                <label for="month" class="form-label">Month</label>
                <select class="form-select" id="month" name="month">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $month == $i ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="year" class="form-label">Year</label>
                <select class="form-select" id="year" name="year">
                    <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                        <option value="<?= $i ?>" <?= $year == $i ? 'selected' : '' ?>>
                            <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="department" class="form-label">Department</label>
                <select class="form-select" id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $key => $name): ?>
                        <option value="<?= $key ?>" 
                                <?= ($filters['department'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter"></i> Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Working Days</h6>
                <h3 class="mb-0"><?= $summary['working_days'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Average Attendance</h6>
                <h3 class="mb-0"><?= number_format($summary['avg_attendance'], 1) ?>%</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Late Instances</h6>
                <h3 class="mb-0"><?= $summary['total_late'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Overtime Hours</h6>
                <h3 class="mb-0"><?= number_format($summary['total_overtime'], 1) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Report Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th rowspan="2">Employee</th>
                        <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                            <th class="text-center" style="min-width: 40px;">
                                <?= $day ?>
                            </th>
                        <?php endfor; ?>
                        <th colspan="5" class="text-center">Summary</th>
                    </tr>
                    <tr>
                        <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                            <th class="text-center small text-muted">
                                <?= substr(date('D', strtotime("$year-$month-$day")), 0, 1) ?>
                            </th>
                        <?php endfor; ?>
                        <th class="text-center">P</th>
                        <th class="text-center">A</th>
                        <th class="text-center">L</th>
                        <th class="text-center">H</th>
                        <th class="text-center">%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report as $employee): ?>
                        <tr>
                            <td>
                                <div><?= htmlspecialchars($employee['name']) ?></div>
                                <small class="text-muted">
                                    <?= htmlspecialchars($employee['department']) ?>
                                </small>
                            </td>
                            <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                                <?php
                                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                $status = $employee['attendance'][$date] ?? null;
                                $class = match($status) {
                                    'present' => 'bg-success text-white',
                                    'absent' => 'bg-danger text-white',
                                    'late' => 'bg-warning',
                                    'half_day' => 'bg-info text-white',
                                    default => ''
                                };
                                $symbol = match($status) {
                                    'present' => '✓',
                                    'absent' => '✗',
                                    'late' => 'L',
                                    'half_day' => 'H',
                                    default => '-'
                                };
                                ?>
                                <td class="text-center <?= $class ?>" style="width: 40px;">
                                    <?= $symbol ?>
                                </td>
                            <?php endfor; ?>
                            <td class="text-center"><?= $employee['summary']['present'] ?></td>
                            <td class="text-center"><?= $employee['summary']['absent'] ?></td>
                            <td class="text-center"><?= $employee['summary']['late'] ?></td>
                            <td class="text-center"><?= $employee['summary']['half_day'] ?></td>
                            <td class="text-center">
                                <?= number_format($employee['summary']['percentage'], 1) ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export report
    document.getElementById('exportReport').addEventListener('click', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', '1');
        window.location.href = '/admin/attendance/report?' + params.toString();
    });

    // Filter change handlers
    ['month', 'year', 'department'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            document.querySelector('form').submit();
        });
    });
});
</script>

<style>
.table th {
    vertical-align: middle;
}
.table td {
    vertical-align: middle;
    padding: 0.5rem;
}
</style>
