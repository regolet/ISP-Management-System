<?php
require_once '../config.php';
check_customer_login();

$page_title = "Make Payment";
include 'header.php';

$bill_id = filter_input(INPUT_GET, 'bill_id', FILTER_SANITIZE_NUMBER_INT);
if (!$bill_id) {
    $_SESSION['error'] = "Invalid bill ID.";
    header("Location: user_subscription.php");
    exit();
}

// Get bill details
$query = "SELECT b.*, c.name as customer_name 
          FROM billing b 
          LEFT JOIN customers c ON b.customer_id = c.id 
          WHERE b.id = ? AND b.customer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $bill_id, $_SESSION['customer_id']);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();

if (!$bill) {
    $_SESSION['error'] = "Bill not found.";
    header("Location: user_subscription.php");
    exit();
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $reference_number = filter_input(INPUT_POST, 'reference_number', FILTER_SANITIZE_STRING);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert payment record
        $stmt = $conn->prepare("INSERT INTO payments (billing_id, amount, payment_date, payment_method, reference_number, status) 
                              VALUES (?, ?, NOW(), ?, ?, 'pending')");
        $stmt->bind_param("idss", $bill_id, $amount, $payment_method, $reference_number);
        
        if (!$stmt->execute()) {
            throw new Exception("Error recording payment: " . $conn->error);
        }
        
        // Update billing status
        $update_stmt = $conn->prepare("UPDATE billing SET status = 'paid' WHERE id = ?");
        $update_stmt->bind_param("i", $bill_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Error updating bill status: " . $conn->error);
        }
        
        // Update customer status
        $update_customer = $conn->prepare("UPDATE customers SET status = 'paid' WHERE id = ?");
        $update_customer->bind_param("i", $_SESSION['customer_id']);
        
        if (!$update_customer->execute()) {
            throw new Exception("Error updating customer status: " . $conn->error);
        }
        
        $conn->commit();
        $_SESSION['success'] = "Payment submitted successfully. It will be verified by our team.";
        header("Location: user_subscription.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<div class="d-flex">
    <?php include 'customer_sidebar.php'; ?>
    
    <div class="flex-grow-1 content-wrapper">
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Make Payment</h4>
                            
                            <?php include 'alerts.php'; ?>
                            
                            <div class="alert alert-info mb-4">
                                <h5>Bill Details</h5>
                                <p class="mb-1"><strong>Invoice #:</strong> <?php echo htmlspecialchars($bill['invoiceid']); ?></p>
                                <p class="mb-1"><strong>Amount Due:</strong> ₱<?php echo number_format($bill['amount'], 2); ?></p>
                                <p class="mb-0"><strong>Due Date:</strong> <?php echo date('F d, Y', strtotime($bill['due_date'])); ?></p>
                            </div>
                            
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Payment Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" id="amount" name="amount" 
                                               step="0.01" value="<?php echo $bill['amount']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Payment Method</label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="">Select payment method</option>
                                        <option value="gcash">GCash</option>
                                        <option value="maya">Maya</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="reference_number" class="form-label">Reference Number</label>
                                    <input type="text" class="form-control" id="reference_number" 
                                           name="reference_number" required>
                                    <div class="form-text">
                                        Please enter the reference number from your payment transaction.
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <a href="user_subscription.php" class="btn btn-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        Submit Payment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.content-wrapper {
    margin-left: 250px;
    padding: 20px;
    min-height: 100vh;
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .content-wrapper {
        margin-left: 0;
        padding: 15px;
    }
}
</style>

<?php include 'footer.php'; ?>
