<?php
require_once 'config.php';
check_login();

$page_title = 'Payroll Details';
$_SESSION['active_menu'] = 'payroll';

// Get type and id parameters
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$id) {
    header('Location: payroll.php');
    exit;
}

include 'header.php';
include 'navbar.php';

// Get payroll period details
$period_query = "SELECT pp.*, 
                COALESCE(COUNT(pi.id), 0) as total_employees,
                COALESCE(SUM(pi.basic_salary), 0) as total_basic,
                COALESCE(SUM(pi.deductions), 0) as total_deductions,
                COALESCE(SUM(pi.net_salary), 0) as total_net
                FROM payroll_periods pp
                LEFT JOIN payroll_items pi ON pp.id = pi.payroll_period_id
                WHERE pp.id = ?
                GROUP BY pp.id";

$stmt = $conn->prepare($period_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$payroll_period = $stmt->get_result()->fetch_assoc();

if (!$payroll_period) {
    $_SESSION['error'] = "Payroll period not found";
    header('Location: payroll.php');
    exit;
}

?>
<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Payroll Period Details</h1>
            <div class="btn-toolbar gap-2">
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bx bx-printer"></i> Print Summary
                </button>
                <a href="payroll.php" class="btn btn-secondary">
                    <i class="bx bx-arrow-back"></i> Back
                </a>
            </div>
        </div>

        <!-- Period Summary -->
        <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Employees</h6>
                        <h3 class="card-title"><?php echo $payroll_period['total_employees']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Basic Salary</h6>
                        <h3 class="card-title">₱<?php echo number_format((float)$payroll_period['total_basic'], 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Deductions</h6>
                        <h3 class="card-title">₱<?php echo number_format((float)$payroll_period['total_deductions'], 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Net Pay</h6>
                        <h3 class="card-title">₱<?php echo number_format((float)$payroll_period['total_net'], 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee List -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Employee Payroll Details</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Basic Salary</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $items_query = "SELECT pi.*, e.first_name, e.last_name, e.employee_code
                                          FROM payroll_items pi
                                          JOIN employees e ON pi.employee_id = e.id
                                          WHERE pi.payroll_period_id = ?";
                            $stmt = $conn->prepare($items_query);
                            $stmt->bind_param("i", $id);
                            $stmt->execute();
                            $items = $stmt->get_result();
                            
                            while ($item = $items->fetch_assoc()):
                            ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($item['employee_code']); ?></small>
                                </td>
                                <td>₱<?php echo number_format((float)$item['basic_salary'], 2); ?></td>
                                <td>₱<?php echo number_format((float)$item['deductions'], 2); ?></td>
                                <td>₱<?php echo number_format((float)$item['net_salary'], 2); ?></td>
                                <td>
                                    <a href="payslip.php?id=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-info" target="_blank">
                                        <i class="bx bx-printer"></i> View Payslip
                                    </a>
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

<?php include 'footer.php'; ?>
