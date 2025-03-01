<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No billing ID provided";
    header('Location: billing.php');
    exit();
}

$billing_id = $_GET['id'];

// Get billing information
$stmt = $conn->prepare("
    SELECT b.*, 
           COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END), 0) as total_paid
    FROM billing b
    LEFT JOIN payments p ON b.id = p.billing_id
    WHERE b.id = ?
    GROUP BY b.id
");
$stmt->bind_param("i", $billing_id);
$stmt->execute();
$result = $stmt->get_result();
$billing = $result->fetch_assoc();

if (!$billing) {
    $_SESSION['error'] = "Billing not found";
    header('Location: billing.php');
    exit();
}

// Calculate remaining balance
$remaining_balance = $billing['amount'] - $billing['total_paid'];

// If bill is already paid, redirect back
if ($billing['status'] === 'paid') {
    $_SESSION['error'] = "This invoice has already been paid in full. Please use the Payments section for any additional payments.";
    header('Location: billing_view.php?id=' . $billing_id);
    exit();
}

// Get payment methods
$payment_methods = $conn->query("SELECT * FROM payment_methods WHERE status = 'active'");

$page_title = "Record Payment";
include 'header.php';
?>

<?php include 'navbar.php'; ?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include 'alerts.php'; ?>

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><?php echo $page_title; ?></h1>
        </div>

        <div class="row justify-content-center">
            <div class="col-md">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Add Payment</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Invoice Details</strong><br>
                            Total Amount: ₱<?php echo number_format($billing['amount'], 2); ?><br>
                            Amount Paid: ₱<?php echo number_format($billing['total_paid'], 2); ?><br>
                            Remaining Balance: ₱<?php echo number_format($remaining_balance, 2); ?>
                        </div>

                        <form action="payment_process.php" method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="billing_id" value="<?php echo $billing_id; ?>">

                            <div class="mb-3">
                                <label for="amount" class="form-label">Payment Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="amount" 
                                           name="amount" 
                                           step="0.01" 
                                           min="0.01" 
                                           value="<?php echo $remaining_balance; ?>"
                                           required>
                                </div>
                                <small class="text-muted">
                                    Remaining Balance: ₱<?php echo number_format($remaining_balance, 2); ?><br>
                                    Overpayments will be added to customer's credit balance.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method_id" id="payment_method" required>
                                    <option value="">Select payment method</option>
                                    <?php
                                    $methods = $conn->query("SELECT id, name FROM payment_methods WHERE status = 'active'");
                                    while ($method = $methods->fetch_assoc()) {
                                        echo '<option value="' . $method['id'] . '">' . htmlspecialchars($method['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                                <div class="invalid-feedback">Please select a payment method</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-control" name="reference_no" id="reference_number"
                                       value="<?php echo isset($_POST['reference_no']) ? htmlspecialchars($_POST['reference_no']) : ''; ?>"
                                       placeholder="Optional for cash payments">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Date</label>
                                <input type="date" class="form-control" name="payment_date" required
                                       value="<?php echo isset($_POST['payment_date']) ? htmlspecialchars($_POST['payment_date']) : date('Y-m-d'); ?>">
                                <div class="invalid-feedback">Please select the payment date</div>
                            </div>

                            <div class="text-end">
                                <a href="billing_view.php?id=<?php echo $billing_id; ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Record Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const methodSelect = document.getElementById('payment_method');
            const refInput = document.getElementById('reference_number');
            
            methodSelect.addEventListener('change', function() {
                const method = this.value;
                refInput.required = (method === 'bank_transfer' || method === 'gcash' || method === 'maya');
            });
        });
    </script>
<?php include 'footer.php'; ?>
<style>
.floating-action-button {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: auto;
    height: auto;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.floating-action-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.35);
}

.floating-action-button i {
    font-size: 1.25rem;
}

@media (max-width: 768px) {
    .floating-action-button {
        padding: 1rem;
        border-radius: 50%;
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .floating-action-button .fab-label {
        display: none;
    }

    .floating-action-button i {
        margin: 0;
    }
}
</style>
