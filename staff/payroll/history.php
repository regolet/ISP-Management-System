<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

// Make sure staff is linked to an employee
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../../login.php");
    exit();
}

$page_title = "Payroll History";
$_SESSION['active_menu'] = 'payroll';

// Get payroll history for the employee
$stmt = $conn->prepare("
    SELECT p.*, 
           COALESCE(p.overtime_pay, 0) as overtime,
           COALESCE(p.allowance, 0) as allowances,
           COALESCE(p.deductions, 0) as deductions
    FROM payroll p
    WHERE p.employee_id = ?
    ORDER BY p.pay_period_end DESC
");

$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
$payroll_records = $stmt->get_result();

include '../../header.php';
include '../staff_navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="h3 mb-0">Payroll History</h1>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Pay Period</th>
                                <th>Basic Pay</th>
                                <th>Overtime</th>
                                <th>Allowances</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($payroll_records->num_rows > 0):
                                while ($record = $payroll_records->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        echo date('M d', strtotime($record['pay_period_start'])) . ' - ' . 
                                             date('M d, Y', strtotime($record['pay_period_end'])); 
                                        ?>
                                    </td>
                                    <td>₱<?php echo number_format($record['basic_pay'], 2); ?></td>
                                    <td>₱<?php echo number_format($record['overtime'], 2); ?></td>
                                    <td>₱<?php echo number_format($record['allowances'], 2); ?></td>
                                    <td>₱<?php echo number_format($record['deductions'], 2); ?></td>
                                    <td>₱<?php echo number_format($record['net_pay'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($record['status']) {
                                                'paid' => 'success',
                                                'approved' => 'info',
                                                default => 'warning'
                                            };
                                        ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="viewPayslip(<?php echo $record['id']; ?>)"></button>
                                            <i class='bx bx-show'></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; 
                            else: ?>
                                <tr></tr>
                                    <td colspan="8" class="text-center">No payroll records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewPayslip(id) {
    window.location.href = 'view_payslip.php?id=' + id;
}
</script>

<?php include '../../footer.php'; ?>
