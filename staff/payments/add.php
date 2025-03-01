<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

$page_title = "Record Payment";
$_SESSION['active_menu'] = 'payments';

// Get unpaid billings
$stmt = $conn->prepare("
    SELECT b.*, 
           b.invoiceid,
           b.billtocustomer as customer_name,
           (b.amount - COALESCE(b.balance, 0)) as amount_due
    FROM billing b
    WHERE b.status = 'unpaid'
    ORDER BY b.due_date ASC
");

$stmt->execute();
$billings = $stmt->get_result();

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        $billing_id = $_POST['billing_id'];
        $amount = floatval($_POST['amount']);
        $payment_method = $_POST['payment_method'];
        $reference_number = $_POST['reference_number'] ?? null;
        $notes = $_POST['notes'] ?? null;

        // Validate amount
        $billing_check = $conn->prepare("SELECT amount, balance FROM billing WHERE id = ?");
        $billing_check->bind_param("i", $billing_id);
        $billing_check->execute();
        $billing = $billing_check->get_result()->fetch_assoc();

        $amount_due = $billing['amount'] - ($billing['balance'] ?? 0);
        
        if ($amount <= 0 || $amount > $amount_due) {
            throw new Exception("Invalid payment amount");
        }

        // Insert payment record
        $stmt = $conn->prepare("
            INSERT INTO payments (
                billing_id, user_id, amount, payment_date, payment_method, 
                reference_number, notes, status, received_by
            ) VALUES (?, ?, ?, NOW(), ?, ?, ?, 'completed', ?)
        ");

        $stmt->bind_param("iidsssi", 
            $billing_id, 
            $_SESSION['user_id'],
            $amount,
            $payment_method,
            $reference_number,
            $notes,
            $_SESSION['user_id']
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to record payment");
        }

        // Update billing balance
        $new_balance = $billing['balance'] + $amount;
        $status = ($new_balance >= $billing['amount']) ? 'paid' : 'unpaid';
        
        $update = $conn->prepare("
            UPDATE billing 
            SET balance = ?, status = ? 
            WHERE id = ?
        ");
        
        $update->bind_param("dsi", $new_balance, $status, $billing_id);
        
        if (!$update->execute()) {
            throw new Exception("Failed to update billing");
        }

        $conn->commit();
        $_SESSION['success'] = "Payment recorded successfully";
        
        log_activity($_SESSION['user_id'], 'payment_recorded', 
            "Recorded payment of ₱" . number_format($amount, 2) . " for billing #" . $billing_id);
        
        header("Location: list.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
}

include '../../header.php';
include '../staff_navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include '../../alerts.php'; ?>

        <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="h3 mb-0">Record Payment</h1>
            </div>
            <div class="col-md-6 text-end">
                <a href="list.php" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Back to List
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="" id="paymentForm">
                    <div class="mb-3">
                        <label class="form-label">Select Bill</label>
                        <select class="form-select" name="billing_id" required>
                            <option value="">Select a bill to pay</option>
                            <?php while ($bill = $billings->fetch_assoc()): ?>
                                <option value="<?php echo $bill['id']; ?>" 
                                        data-amount="<?php echo $bill['amount_due']; ?>">
                                    <?php echo htmlspecialchars($bill['invoiceid'] . ' - ' . 
                                          $bill['customer_name'] . ' (₱' . 
                                          number_format($bill['amount_due'], 2) . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" class="form-control" name="amount" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="gcash">GCash</option>
                            <option value="maya">Maya</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reference Number</label>
                        <input type="text" class="form-control" name="reference_number">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const billSelect = this.querySelector('[name="billing_id"]');
    const amountInput = this.querySelector('[name="amount"]');
    
    const selectedOption = billSelect.options[billSelect.selectedIndex];
    const maxAmount = parseFloat(selectedOption.dataset.amount);
    const amount = parseFloat(amountInput.value);

    if (amount <= 0 || amount > maxAmount) {
        e.preventDefault();
        alert('Invalid payment amount. Amount must be between 0 and ' + maxAmount);
    }
});
</script>

<?php include '../../footer.php'; ?>
