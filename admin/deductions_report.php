<?php
require_once 'config.php';
check_login();

$page_title = "Deductions Report";
$_SESSION['active_menu'] = 'deductions_report';
include 'header.php';
include 'navbar.php';

// Get date filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$department = isset($_GET['department']) ? clean_input($_GET['department']) : '';
$deduction_type = isset($_GET['deduction_type']) ? clean_input($_GET['deduction_type']) : '';

// Get deductions summary
$summary_query = "
    SELECT 
        COUNT(DISTINCT ed.employee_id) as total_employees,
        COUNT(ed.id) as total_deductions,
        SUM(ed.amount) as total_amount,
        COUNT(DISTINCT dt.type) as unique_types
    FROM employee_deductions ed
    JOIN employees e ON ed.employee_id = e.id
    JOIN deduction_types dt ON ed.deduction_type_id = dt.id
    WHERE ed.status = 'active'
    AND (ed.end_date IS NULL OR ed.end_date >= ?)
    AND ed.start_date <= ?";

if ($department) {
    $summary_query .= " AND e.department = ?";
}
if ($deduction_type) {
    $summary_query .= " AND dt.type = ?";
}

$stmt = $conn->prepare($summary_query);

if ($department && $deduction_type) {
    $stmt->bind_param("ssss", $end_date, $start_date, $department, $deduction_type);
} elseif ($department) {
    $stmt->bind_param("sss", $end_date, $start_date, $department);
} elseif ($deduction_type) {
    $stmt->bind_param("sss", $end_date, $start_date, $deduction_type);
} else {
    $stmt->bind_param("ss", $end_date, $start_date);
}

$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

// Get deduction types summary
$types_query = "
    SELECT 
        dt.type,
        COUNT(ed.id) as count,
        SUM(ed.amount) as total_amount
    FROM employee_deductions ed
    JOIN employees e ON ed.employee_id = e.id
    JOIN deduction_types dt ON ed.deduction_type_id = dt.id
    WHERE ed.status = 'active'
    AND (ed.end_date IS NULL OR ed.end_date >= ?)
    AND ed.start_date <= ?";

if ($department) {
    $types_query .= " AND e.department = ?";
}

$types_query .= " GROUP BY dt.type ORDER BY total_amount DESC";

$stmt = $conn->prepare($types_query);
if ($department) {
    $stmt->bind_param("sss", $end_date, $start_date, $department);
} else {
    $stmt->bind_param("ss", $end_date, $start_date);
}
$stmt->execute();
$types_result = $stmt->get_result();

// Get detailed deductions records
$query = "
    SELECT 
        ed.*,
        e.employee_code,
        e.first_name,
        e.last_name,
        e.department,
        e.position,
        dt.name as deduction_name,
        dt.type as deduction_type
    FROM employee_deductions ed
    JOIN employees e ON ed.employee_id = e.id
    JOIN deduction_types dt ON ed.deduction_type_id = dt.id
    WHERE ed.status = 'active'
    AND (ed.end_date IS NULL OR ed.end_date >= ?)
    AND ed.start_date <= ?";

if ($department) {
    $query .= " AND e.department = ?";
}
if ($deduction_type) {
    $query .= " AND dt.type = ?";
}

$query .= " ORDER BY e.last_name, e.first_name, dt.name";

$stmt = $conn->prepare($query);

if ($department && $deduction_type) {
    $stmt->bind_param("ssss", $end_date, $start_date, $department, $deduction_type);
} elseif ($department) {
    $stmt->bind_param("sss", $end_date, $start_date, $department);
} elseif ($deduction_type) {
    $stmt->bind_param("sss", $end_date, $start_date, $deduction_type);
} else {
    $stmt->bind_param("ss", $end_date, $start_date);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include 'alerts.php'; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Deductions Report</h1>
            <div class="btn-toolbar gap-2">
                <button type="button" class="btn btn-success" onclick="exportToExcel()">
                    <i class='bx bx-export'></i> Export to Excel
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" 
                               value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" 
                               value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department">
                            <option value="">All Departments</option>
                            <?php
                            $dept_query = "SELECT DISTINCT department FROM employees ORDER BY department";
                            $departments = $conn->query($dept_query);
                            while ($dept = $departments->fetch_assoc()):
                                $selected = ($department === $dept['department']) ? 'selected' : '';
                            ?>
                            <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($dept['department']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Deduction Type</label>
                        <select class="form-select" name="deduction_type">
                            <option value="">All Types</option>
                            <?php
                            $types = ['SSS', 'PhilHealth', 'Pag-IBIG', 'Tax', 'Loan', 'Other'];
                            foreach ($types as $type):
                                $selected = ($deduction_type === $type) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $type; ?>" <?php echo $selected; ?>>
                                <?php echo $type; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-filter-alt'></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
            <!-- Total Employees -->
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Employees</h6>
                                <h3 class="card-title mb-0"><?php echo number_format($summary['total_employees'] ?? 0); ?></h3>
                            </div>
                            <div class="icon-shape bg-light text-primary rounded p-3">
                                <i class="bx bx-user-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Deductions -->
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Deductions</h6>
                                <h3 class="card-title mb-0 text-success"><?php echo number_format($summary['total_deductions'] ?? 0); ?></h3>
                            </div>
                            <div class="icon-shape bg-light text-success rounded p-3">
                                <i class="bx bx-list-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Amount -->
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Amount</h6>
                                <h3 class="card-title mb-0 text-info">₱<?php echo number_format($summary['total_amount'] ?? 0, 2); ?></h3>
                            </div>
                            <div class="icon-shape bg-light text-info rounded p-3">
                                <i class="bx bx-money"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deduction Types -->
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Deduction Types</h6>
                                <h3 class="card-title mb-0 text-warning"><?php echo number_format($summary['unique_types'] ?? 0); ?></h3>
                            </div>
                            <div class="icon-shape bg-light text-warning rounded p-3">
                                <i class="bx bx-category"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deduction Types Summary -->
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">Deductions by Type</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Count</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($type = $types_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($type['type']); ?></td>
                                <td><?php echo number_format($type['count']); ?></td>
                                <td>₱<?php echo number_format($type['total_amount'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detailed Records -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Detailed Records</h4>
                <div class="table-responsive">
                    <table class="table table-hover" id="deductionsTable">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name']); ?></div>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($row['employee_code']); ?> - 
                                        <?php echo htmlspecialchars($row['department']); ?>
                                    </small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['deduction_type']); ?></td>
                                <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['deduction_name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($row['status']) {
                                            'active' => 'success',
                                            'pending' => 'warning',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-shape {
    width: 4rem;
    height: 4rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
.card {
    transition: transform 0.2s ease-in-out;
}
.card:hover {
    transform: translateY(-3px);
}
.fs-1 {
    font-size: 2rem !important;
}
</style>

<script>
function exportToExcel() {
    // Get the table
    let table = document.getElementById("deductionsTable");
    
    // Convert table to Excel format
    let html = table.outerHTML;
    
    // Create a Blob with the HTML content
    let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    
    // Create download link
    let url = window.URL.createObjectURL(blob);
    let a = document.createElement("a");
    a.href = url;
    a.download = 'deductions_report_<?php echo date("Y-m-d"); ?>.xls';
    
    // Trigger download
    document.body.appendChild(a);
    a.click();
    
    // Cleanup
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}
</script>

<?php include 'footer.php'; ?>
