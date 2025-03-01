<?php
require_once 'config.php';
check_login();

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'deduction_history'");
if ($table_check->num_rows == 0) {
    // Table doesn't exist, create it
    $create_table_sql = file_get_contents(__DIR__ . '/sql/create_deduction_history.sql');
    if (!$conn->multi_query($create_table_sql)) {
        die("Error creating deduction_history table: " . $conn->error);
    }
    while ($conn->more_results()) {
        $conn->next_result();
    }
}

$page_title = 'Deduction History';
$_SESSION['active_menu'] = 'deductions';

if (!isset($_GET['id'])) {
    header('Location: deductions.php');
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Get deduction details
$query = "SELECT ed.*, e.first_name, e.last_name, e.employee_code, 
          dt.name as deduction_name, dt.type as deduction_type
          FROM employee_deductions ed
          JOIN employees e ON ed.employee_id = e.id
          JOIN deduction_types dt ON ed.deduction_type_id = dt.id
          WHERE ed.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$deduction = $stmt->get_result()->fetch_assoc();

if (!$deduction) {
    header('Location: deductions.php');
    exit;
}

// Get deduction history
$history_query = "SELECT * FROM deduction_history 
                 WHERE deduction_id = ? 
                 ORDER BY created_at DESC";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$history = $stmt->get_result();

include 'header.php';
?>

<?php include 'navbar.php'; ?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Deduction History</h1>
            <a href="deductions.php" class="btn btn-secondary">
                <i class='bx bx-arrow-back'></i> Back to Deductions
            </a>
        </div>

        <!-- Deduction Details Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Deduction Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Employee:</strong> <?php echo htmlspecialchars($deduction['first_name'] . ' ' . $deduction['last_name']); ?></p>
                        <p><strong>Employee Code:</strong> <?php echo htmlspecialchars($deduction['employee_code']); ?></p>
                        <p><strong>Deduction Type:</strong> <?php echo htmlspecialchars($deduction['deduction_name']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Amount:</strong> ₱<?php echo number_format($deduction['amount'], 2); ?></p>
                        <p><strong>Frequency:</strong> <?php echo ucfirst($deduction['frequency']); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($deduction['status']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Transaction History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo ucfirst($row['transaction_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['notes']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($history->num_rows === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center">No history found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
