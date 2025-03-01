<?php
require_once __DIR__ . '/../config.php';
check_auth('customer');

// Get customer data
$customer_query = "SELECT * FROM customers WHERE user_id = ?";
$stmt = $conn->prepare($customer_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

// Get payment methods
$payment_methods_query = "SELECT * FROM payment_methods WHERE status = 'active'";
$payment_methods = $conn->query($payment_methods_query);

// Get payment history
$payments_query = "SELECT p.*, pm.name as payment_method_name, b.invoiceid 
                  FROM payments p 
                  LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
                  LEFT JOIN billing b ON p.billing_id = b.id
                  WHERE p.user_id = ? 
                  ORDER BY p.created_at DESC";
$stmt = $conn->prepare($payments_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$payments_result = $stmt->get_result();

// Set page title
$page_title = 'Payments';
include __DIR__ . '/../header.php';
include __DIR__ . '/navbar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Payments</h1>

        <?php if (isset($_GET['bill_id'])): ?>
            <!-- Payment Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Make Payment</h6>
                </div>
                <div class="card-body">
                    <form action="process_payment.php" method="POST">
                        <input type="hidden" name="billing_id" value="<?php echo $_GET['bill_id']; ?>">
                        
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="payment_method_id" class="form-control" required>
                                <option value="">Select Payment Method</option>
                                <?php while ($method = $payment_methods->fetch_assoc()): ?>
                                    <option value="<?php echo $method['id']; ?>">
                                        <?php echo htmlspecialchars($method['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="amount" class="form-control" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label>Reference Number</label>
                            <input type="text" name="reference_no" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit Payment</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Payment History -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Payment History</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice ID</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Reference</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($payment = $payments_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($payment['invoiceid']); ?></td>
                                    <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_method_name']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['reference_no']); ?></td>
                                    <td><?php echo ucfirst($payment['status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
