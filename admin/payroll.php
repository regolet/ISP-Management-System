<?php
require_once '../config.php';
check_auth();

$page_title = 'Payroll Management';
$_SESSION['active_menu'] = 'payroll';
include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Payroll Management</h1>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" 
                onclick="window.location.href='payroll_add.php'">
            <i class="bx bx-plus"></i>
            <span>Create Payroll Period</span>
        </button>
    </div>

    <!-- Payroll Periods -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Pay Date</th>
                            <th>Employees</th>
                            <th>Total Basic</th>
                            <th>Total Deductions</th>
                            <th>Total Net</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "
                            SELECT 
                                p.*,
                                COUNT(DISTINCT pi.employee_id) as employee_count,
                                SUM(pi.basic_salary) as total_basic,
                                SUM(pi.sss_contribution + pi.philhealth_contribution + 
                                    pi.pagibig_contribution + pi.tax_contribution + 
                                    pi.other_deductions) as total_deductions,
                                SUM(pi.net_salary) as total_net
                            FROM payroll_periods p
                            LEFT JOIN payroll_items pi ON p.id = pi.payroll_period_id
                            GROUP BY p.id
                            ORDER BY p.period_start DESC
                        ";
                        $result = $conn->query($query);
                        
                        while ($period = $result->fetch_assoc()):
                            $status_class = [
                                'draft' => 'secondary',
                                'processing' => 'info',
                                'approved' => 'success',
                                'paid' => 'primary'
                            ][$period['status']];
                        ?>
                        <tr>
                            <td>
                                <div><?php echo date('F d, Y', strtotime($period['period_start'])); ?></div>
                                <small class="text-muted">to <?php echo date('F d, Y', strtotime($period['period_end'])); ?></small>
                            </td>
                            <td><?php echo date('F d, Y', strtotime($period['pay_date'])); ?></td>
                            <td class="text-center"><?php echo $period['employee_count']; ?></td>
                            <td class="text-end">₱<?php echo number_format($period['total_basic'], 2); ?></td>
                            <td class="text-end">₱<?php echo number_format($period['total_deductions'], 2); ?></td>
                            <td class="text-end">₱<?php echo number_format($period['total_net'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $period['status'] == 'paid' ? 'success' : 
                                        ($period['status'] == 'approved' ? 'info' : 
                                        ($period['status'] == 'processing' ? 'warning' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst($period['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="payroll_view.php?id=<?php echo $period['id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    <?php if ($period['status'] == 'draft'): ?>
                                    <a href="payroll_edit.php?id=<?php echo $period['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="deletePayrollPeriod(<?php echo $period['id']; ?>)">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function deletePayrollPeriod(id) {
    if (confirm('Are you sure you want to delete this payroll period? This will also delete all related records.')) {
        fetch('payroll_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting payroll period');
        });
    }
}
</script>

<?php include 'footer.php'; ?>
