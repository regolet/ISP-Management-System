<?php
require_once 'config.php';
check_login();

$page_title = 'View Payslip';
$_SESSION['active_menu'] = 'payroll';
include 'header.php';

$item_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$item_id) {
    $_SESSION['error'] = "Invalid payslip ID";
    header("Location: payroll.php");
    exit();
}

// Get payslip details with COALESCE and proper calculations
$query = "SELECT 
    pi.*,
    p.period_start,
    p.period_end,
    p.pay_date,
    p.status as payroll_status,
    e.employee_code,
    e.first_name,
    e.last_name,
    e.position,
    e.department,
    COALESCE(pi.basic_salary, 0) as basic_salary,
    COALESCE(pi.allowance, 0) as allowance,
    COALESCE(pi.overtime_amount, 0) as overtime_amount,
    COALESCE(pi.overtime_hours, 0) as overtime_hours,
    COALESCE(pi.deductions, 0) as total_deductions,
    COALESCE(pi.net_salary, 0) as net_salary,
    pi.deduction_details
FROM payroll_items pi
JOIN payroll_periods p ON pi.payroll_period_id = p.id
JOIN employees e ON pi.employee_id = e.id
WHERE pi.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$payslip = $stmt->get_result()->fetch_assoc();

if (!$payslip) {
    $_SESSION['error'] = "Payslip not found";
    header("Location: payroll.php");
    exit();
}

// Calculate totals with proper type casting
$total_earnings = (float)$payslip['basic_salary'] + 
                 (float)$payslip['allowance'] + 
                 (float)$payslip['overtime_amount'];

// Get deductions from JSON
$deduction_details = json_decode($payslip['deduction_details'] ?? '[]', true) ?: [];
$total_deductions = array_sum(array_column($deduction_details, 'amount'));

// Verify net salary matches calculations
$calculated_net = $total_earnings - $total_deductions;

// Log any discrepancy for debugging
if (abs($calculated_net - $payslip['net_salary']) > 0.01) {
    error_log("Net salary mismatch - Stored: {$payslip['net_salary']}, Calculated: {$calculated_net}");
}

// Use calculated net salary to ensure accuracy
$net_salary = $calculated_net;
?>

<?php include 'navbar.php'; ?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include 'alerts.php'; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Payslip Details</h1>
            <div class="btn-toolbar gap-2">
                <button type="button" class="btn btn-primary" onclick="printPayslip(<?php echo $item_id; ?>)">
                    <i class='bx bx-printer'></i> Print Payslip
                </button>
                <a href="payroll_view.php?id=<?php echo $payslip['payroll_period_id']; ?>" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Back
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Employee & Period Info -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Employee Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <th width="150">Employee ID:</th>
                                <td><?php echo htmlspecialchars($payslip['employee_code']); ?></td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td><?php echo htmlspecialchars($payslip['first_name'] . ' ' . $payslip['last_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Position:</th>
                                <td><?php echo htmlspecialchars($payslip['position']); ?></td>
                            </tr>
                            <tr>
                                <th>Department:</th>
                                <td><?php echo htmlspecialchars($payslip['department']); ?></td>
                            </tr>
                            <tr>
                                <th>Pay Period:</th>
                                <td><?php echo date('M d', strtotime($payslip['period_start'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($payslip['period_end'])); ?></td>
                            </tr>
                            <tr>
                                <th>Pay Date:</th>
                                <td><?php echo date('M d, Y', strtotime($payslip['pay_date'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Salary Details -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Payroll Details</h5>
                        <div class="row">
                            <!-- Earnings -->
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Earnings</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td>Basic Salary</td>
                                        <td class="text-end">₱<?php echo number_format((float)$payslip['basic_salary'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Allowance</td>
                                        <td class="text-end">₱<?php echo number_format((float)$payslip['allowance'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Overtime (<?php echo (int)$payslip['overtime_hours']; ?> hrs)</td>
                                        <td class="text-end">₱<?php echo number_format((float)$payslip['overtime_amount'], 2); ?></td>
                                    </tr>
                                    <tr class="table-secondary">
                                        <th>Total Earnings</th>
                                        <th class="text-end">₱<?php echo number_format($total_earnings, 2); ?></th>
                                    </tr>
                                </table>
                            </div>

                            <!-- Deductions -->
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Deductions</h6>
                                <table class="table table-sm">
                                    <?php foreach ($deduction_details as $deduction): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($deduction['name'] ?? 'Unknown Deduction'); ?></td>
                                        <td class="text-end">₱<?php echo number_format((float)($deduction['amount'] ?? 0), 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-secondary">
                                        <th>Total Deductions</th>
                                        <th class="text-end">₱<?php echo number_format($total_deductions, 2); ?></th>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Net Pay -->
                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="mb-0">Net Pay</h5>
                                </div>
                                <div class="col text-end">
                                    <h4 class="mb-0">₱<?php echo number_format((float)$net_salary, 2); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.content-wrapper {
    margin-left: 250px; /* Adjust based on your sidebar width */
    padding: 20px;
    min-height: calc(100vh - 60px); /* Adjust based on your header height */
    background: #f4f6f9;
}

@media (max-width: 768px) {
    .content-wrapper {
        margin-left: 0;
    }
}

.card {
    margin-bottom: 1rem;
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
}

.container-fluid {
    max-width: 1400px;
    margin: 0 auto;
}
</style>

<script>
function printPayslip(id) {
    window.open(`payslip_print.php?id=${id}`, '_blank');
}
</script>

<?php include 'footer.php'; ?>
