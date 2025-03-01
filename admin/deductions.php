<?php
require_once '../config.php';
check_auth();

$page_title = 'Deduction Management';
$_SESSION['active_menu'] = 'deductions';
include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Deduction Management</h1>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" 
                data-bs-toggle="modal" data-bs-target="#addDeductionModal">
            <i class="bx bx-plus"></i>
            <span>Add Deduction</span>
        </button>
    </div>

    <!-- Deduction Types Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Deduction Types</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Calculation</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $types_query = "SELECT * FROM deduction_types ORDER BY type, name";
                        $types = $conn->query($types_query);
                        
                        while ($type = $types->fetch_assoc()): 
                        ?>
                        <tr>
                            <td>
                                <div><?php echo htmlspecialchars($type['name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($type['description']); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $type['type'] == 'government' ? 'info' : 
                                    ($type['type'] == 'loan' ? 'warning' : 'secondary'); ?>">
                                    <?php echo ucfirst($type['type']); ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst($type['calculation_type']); ?></td>
                            <td>
                                <?php if ($type['calculation_type'] == 'percentage'): ?>
                                    <?php echo number_format($type['percentage_value'], 2); ?>%
                                <?php else: ?>
                                    Fixed Amount
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $type['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $type['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" 
                                        onclick="editDeductionType(<?php echo $type['id']; ?>)">
                                    <i class="bx bx-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="deleteDeductionType(<?php echo $type['id']; ?>)">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Active Deductions Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Active Deductions</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Deduction</th>
                            <th>Amount</th>
                            <th>Frequency</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $deductions_query = "SELECT ed.*, 
                                            e.first_name, e.last_name, e.employee_code,
                                            dt.name as deduction_name, dt.type as deduction_type
                                            FROM employee_deductions ed
                                            JOIN employees e ON ed.employee_id = e.id
                                            JOIN deduction_types dt ON ed.deduction_type_id = dt.id
                                            WHERE ed.status = 'active'
                                            ORDER BY e.last_name, e.first_name, dt.name";
                        $deductions = $conn->query($deductions_query);
                        
                        while ($deduction = $deductions->fetch_assoc()): 
                        ?>
                        <tr>
                            <td>
                                <div><?php echo htmlspecialchars($deduction['first_name'] . ' ' . $deduction['last_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($deduction['employee_code']); ?></small>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($deduction['deduction_name']); ?></div>
                                <span class="badge bg-<?php echo $deduction['deduction_type'] == 'government' ? 'info' : 
                                    ($deduction['deduction_type'] == 'loan' ? 'warning' : 'secondary'); ?>">
                                    <?php echo ucfirst($deduction['deduction_type']); ?>
                                </span>
                            </td>
                            <td>₱<?php echo number_format($deduction['amount'], 2); ?></td>
                            <td><?php echo ucfirst($deduction['frequency']); ?></td>
                            <td>
                                <div>From: <?php echo date('M d, Y', strtotime($deduction['start_date'])); ?></div>
                                <?php if ($deduction['end_date']): ?>
                                    <small>To: <?php echo date('M d, Y', strtotime($deduction['end_date'])); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $deduction['status'] == 'active' ? 'success' : 
                                    ($deduction['status'] == 'completed' ? 'info' : 'secondary'); ?>">
                                    <?php echo ucfirst($deduction['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="viewDeductionHistory(<?php echo $deduction['id']; ?>)">
                                        <i class="bx bx-history"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            onclick="editDeduction(<?php echo $deduction['id']; ?>)">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="cancelDeduction(<?php echo $deduction['id']; ?>)">
                                        <i class="bx bx-x"></i>
                                    </button>
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

<!-- Add Deduction Modal -->
<div class="modal fade" id="addDeductionModal" tabindex="-1" aria-labelledby="addDeductionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="save_deduction.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDeductionModalLabel">Add Deduction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Employee</option>
                            <?php
                            $emp_query = "SELECT id, employee_code, first_name, last_name FROM employees WHERE status = 'active'";
                            $employees = $conn->query($emp_query);
                            while ($emp = $employees->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $emp['id']; ?>">
                                <?php echo htmlspecialchars($emp['employee_code'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Deduction Type</label>
                        <select name="deduction_type_id" class="form-select" required>
                            <option value="">Select Deduction Type</option>
                            <?php
                            $types_query = "SELECT id, name, type FROM deduction_types WHERE is_active = 1";
                            $types = $conn->query($types_query);
                            while ($type = $types->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $type['id']; ?>">
                                <?php echo htmlspecialchars($type['name'] . ' (' . ucfirst($type['type']) . ')'); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Frequency</label>
                        <select name="frequency" class="form-select" required>
                            <option value="onetime">One Time</option>
                            <option value="monthly">Monthly</option>
                            <option value="bimonthly">Bi-Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annual">Annual</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date">
                                <small class="text-muted">Optional for recurring deductions</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Deduction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Initialize Bootstrap modals
    document.addEventListener('DOMContentLoaded', function() {
        var deductionModal = new bootstrap.Modal(document.getElementById('addDeductionModal'));
    });
</script>

<?php include 'footer.php'; ?>
