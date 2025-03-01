<?php
require_once 'config.php';
check_login();

$page_title = 'Edit Payroll';
$_SESSION['active_menu'] = 'payroll';

if (!isset($_GET['id'])) {
    header('Location: payroll.php');
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Get payroll period details
$period_query = "SELECT * FROM payroll_periods WHERE id = ?";
$stmt = $conn->prepare($period_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$period = $stmt->get_result()->fetch_assoc();

if (!$period) {
    $_SESSION['error'] = "Payroll period not found";
    header('Location: payroll.php');
    exit;
}

include 'header.php';
?>

<?php include 'navbar.php'; ?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include 'alerts.php'; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Edit Payroll Period</h1>
            <div class="btn-toolbar gap-2">
                <button type="submit" form="payrollForm" class="btn btn-primary">
                    <i class="bx bx-save"></i> Save Changes
                </button>
                <a href="payroll.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                    <i class="bx bx-arrow-back"></i> Back
                </a>
            </div>
        </div>

        <form method="POST" action="" id="payrollForm">
            <div class="mb-3">
                <label for="period_start" class="form-label">Period Start</label>
                <input type="date" class="form-control" id="period_start" name="period_start" 
                       value="<?php echo $period['period_start']; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="period_end" class="form-label">Period End</label>
                <input type="date" class="form-control" id="period_end" name="period_end" 
                       value="<?php echo $period['period_end']; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="pay_date" class="form-label">Pay Date</label>
                <input type="date" class="form-control" id="pay_date" name="pay_date" 
                       value="<?php echo $period['pay_date']; ?>" required>
            </div>
        </form>
    </div>
</div>

<style>
.content-wrapper {
    margin-left: 250px;
    padding: 20px;
    min-height: calc(100vh - 60px);
    background: #f4f6f9;
}

@media (max-width: 768px) {
    .content-wrapper {
        margin-left: 0;
    }
}

.container-fluid {
    max-width: 1400px;
    margin: 0 auto;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add date validation
    var periodStart = document.getElementById('period_start');
    var periodEnd = document.getElementById('period_end');
    var payDate = document.getElementById('pay_date');
    
    periodStart.addEventListener('change', function() {
        periodEnd.min = this.value;
        if (periodEnd.value && periodEnd.value < this.value) {
            periodEnd.value = this.value;
        }
    });
    
    periodEnd.addEventListener('change', function() {
        periodStart.max = this.value;
        payDate.min = this.value;
        if (payDate.value && payDate.value < this.value) {
            payDate.value = this.value;
        }
    });
});
</script>

<?php include 'footer.php'; ?>
