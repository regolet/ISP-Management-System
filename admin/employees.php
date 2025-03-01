<?php
require_once '../config.php';
check_auth();

$page_title = 'Employee Management';
$_SESSION['active_menu'] = 'employees';
include 'header.php';
include 'navbar.php';
?>

<div class="container-fluid py-4">
    <?php include 'alerts.php'; ?>
    
    <!-- Header -->
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Employee Management</h1>
    </div>

    <!-- Employee List -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee Code</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Daily Rate</th>
                            <th>Status</th>
                            <th width="140">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM employees ORDER BY last_name, first_name";
                        $result = $conn->query($query);
                        
                        while ($emp = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($emp['employee_code']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($emp['position']); ?></td>
                            <td><?php echo htmlspecialchars($emp['department']); ?></td>
                            <td>₱<?php echo number_format($emp['daily_rate'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $emp['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($emp['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="employee_view.php?id=<?php echo $emp['id']; ?>" 
                                       class="btn btn-sm btn-info" title="View Details">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    <a href="employee_view.php?id=<?php echo $emp['id']; ?>&edit=true" 
                                       class="btn btn-sm btn-primary" title="Edit Employee">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <?php if ($emp['status'] == 'active'): ?>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="deactivateEmployee(<?php echo $emp['id']; ?>)" 
                                            title="Deactivate Employee">
                                        <i class="bx bx-user-x"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-success" 
                                            onclick="activateEmployee(<?php echo $emp['id']; ?>)" 
                                            title="Activate Employee">
                                        <i class="bx bx-user-check"></i>
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

    <!-- Floating Action Button -->
    <a href="employee_add.php" class="btn btn-primary position-fixed" 
       style="bottom: 2rem; right: 2rem; border-radius: 50px; padding: 0.5rem 1rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 10px rgba(0,0,0,0.3);">
<i class='bx bx-user-plus fs-5'></i> Add Employees
    </a>

    <script>
    function deactivateEmployee(id) {
        if (confirm('Are you sure you want to deactivate this employee?')) {
            window.location.href = `employee_status.php?id=${id}&status=inactive`;
        }
    }

    function activateEmployee(id) {
        if (confirm('Are you sure you want to activate this employee?')) {
            window.location.href = `employee_status.php?id=${id}&status=active`;
        }
    }
    </script>

    <style>
    .content-wrapper {
        margin-left: 250px;
        padding: 20px;
        min-height: calc(100vh - 60px);
        background: #f8f9fa;
    }

    @media (max-width: 768px) {
        .content-wrapper {
            margin-left: 0;
        }
    }

    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    .table td {
        vertical-align: middle;
    }

    .btn-group .btn {
        padding: 0.25rem 0.5rem;
    }

    .btn-group .btn i {
        font-size: 1.1rem;
        line-height: 1;
    }

    .badge {
        padding: 0.5em 0.8em;
        font-weight: 500;
    }
    </style>

    <?php include 'footer.php'; ?>
</div>
