<?php
require_once '../config.php';
check_auth();

$page_title = "Payroll Report";
$_SESSION['active_menu'] = 'payroll_report';
include 'header.php';
include 'navbar.php';

// Get date filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$department = isset($_GET['department']) ? clean_input($_GET['department']) : '';

// Get payroll summary
$summary_query = "
    SELECT 
        COUNT(DISTINCT p.employee_id) as total_employees,
        SUM(p.basic_pay) as total_basic_pay,
        SUM(p.overtime_pay) as total_overtime,
        SUM(p.allowance) as total_allowances,
        SUM(p.deductions) as total_deductions,
        SUM(p.net_pay) as total_net_pay
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    WHERE p.pay_period_end BETWEEN ? AND ?";

if ($department) {
    $summary_query .= " AND e.department = ?";
}

$stmt = $conn->prepare($summary_query);
if ($department) {
    $stmt->bind_param("sss", $start_date, $end_date, $department);
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
}
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

// Get detailed payroll records
$query = "
    SELECT p.*, 
           e.employee_code,
           e.first_name,
           e.last_name,
           e.department,
           e.position
    FROM payroll p
    JOIN employees e ON p.employee_id = e.id
    WHERE p.pay_period_end BETWEEN ? AND ?";

if ($department) {
    $query .= " AND e.department = ?";
}

$query .= " ORDER BY p.pay_period_end DESC, e.last_name, e.first_name";

$stmt = $conn->prepare($query);
if ($department) {
    $stmt->bind_param("sss", $start_date, $end_date, $department);
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include 'alerts.php'; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Payroll Report</h1>
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
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" 
                               value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" 
                               value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
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
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-filter-alt'></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
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

            <!-- Total Basic Pay -->
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Basic Pay</h6>
                                <h3 class="card-title mb-0 text-success">₱<?php echo number_format($summary['total_basic_pay'] ?? 0, 2); ?></h3>
                            </div>
                            <div class="icon-shape bg-light text-success rounded p-3">
                                <i class="bx bx-money"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Net Pay -->
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Net Pay</h6>
                                <h3 class="card-title mb-0 text-info">₱<?php echo number_format($summary['total_net_pay'] ?? 0, 2); ?></h3>
                            </div>
                            <div class="icon-shape bg-light text-info rounded p-3">
                                <i class="bx bx-wallet"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Summary -->
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
            <!-- Total Overtime Pay -->
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Overtime Pay</h6>
                                <h3 class="card-title mb-0 text-warning">₱<?php echo number_format($summary['total_overtime'] ?? 0, 2); ?></h3>
                            </div>
                            <div class="icon-shape bg-light text-warning rounded p-3">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Allowances -->
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Allowances</h6>
                                <h3 class="card-title mb-0 text-success">₱<?php echo number_format($summary['total_allowances'] ?? 0, 2); ?></h3>
                            </div>
                            <div class="icon-shape bg-light text-success rounded p-3">
                                <i class="bx bx-plus-circle"></i>
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
                                <h3 class="card-title mb-0 text-danger">₱<?php echo number_format($summary['total_deductions'] ?? 0, 2); ?></h3>
                            </div>
                            <div class="icon-shape bg-light text-danger rounded p-3">
                                <i class="bx bx-minus-circle"></i>
                            </div>
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

        <!-- Detailed Records -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Detailed Records</h4>
                <div class="table-responsive">
                    <table class="table table-hover" id="payrollTable">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Pay Period</th>
                                <th>Basic Pay</th>
                                <th>Overtime</th>
                                <th>Allowances</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
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
                                        <?php echo htmlspecialchars($row['position']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php 
                                    echo date('M d', strtotime($row['pay_period_start'])) . ' - ' . 
                                         date('M d, Y', strtotime($row['pay_period_end'])); 
                                    ?>
                                </td>
                                <td>₱<?php echo number_format($row['basic_pay'], 2); ?></td>
                                <td>₱<?php echo number_format($row['overtime_pay'], 2); ?></td>
                                <td>₱<?php echo number_format($row['allowance'], 2); ?></td>
                                <td>₱<?php echo number_format($row['deductions'], 2); ?></td>
                                <td>₱<?php echo number_format($row['net_pay'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($row['status']) {
                                            'paid' => 'success',
                                            'pending' => 'warning',
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

<script>
function exportToExcel() {
    // Get the table
    let table = document.getElementById("payrollTable");
    
    // Convert table to Excel format
    let html = table.outerHTML;
    
    // Create a Blob with the HTML content
    let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    
    // Create download link
    let url = window.URL.createObjectURL(blob);
    let a = document.createElement("a");
    a.href = url;
    a.download = 'payroll_report_<?php echo date("Y-m-d"); ?>.xls';
    
    // Trigger download
    document.body.appendChild(a);
    a.click();
    
    // Cleanup
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}
</script>

<?php include 'footer.php'; ?>
