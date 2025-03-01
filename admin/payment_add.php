<?php
require_once '../config.php';
check_auth();

$_SESSION['active_menu'] = 'payments';
include 'header.php';
include 'navbar.php';

// Get all unpaid/partial billings
$billing_query = "SELECT b.*, 
                 c.name as customer_name,
                 c.credit_balance,
                 c.outstanding_balance,
                 (SELECT COALESCE(SUM(amount), 0) 
                  FROM payments 
                  WHERE billing_id = b.id 
                  AND status = 'completed') as paid_amount
                 FROM billing b
                 LEFT JOIN customers c ON b.customer_id = c.id
                 WHERE b.status IN ('unpaid', 'partial')
                 ORDER BY b.due_date ASC";
$billings = $conn->query($billing_query);

// Get payment methods
$method_query = "SELECT * FROM payment_methods ORDER BY name ASC";
$payment_methods = $conn->query($method_query);
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="card-title">Add New Payment</h4>
                    </div>
                    <div class="card-body">
                        <form action="payment_process.php" method="POST" id="paymentForm">
                            <!-- Billing Selection -->
                            <div class="mb-3">
                                <label for="billing_id" class="form-label">Select Bill</label>
                                <select class="form-select" name="billing_id" id="billing_id" required>
                                    <option value="">Select a bill...</option>
                                    <?php while ($bill = $billings->fetch_assoc()): 
                                        $balance = $bill['amount'] - $bill['paid_amount'];
                                    ?>
                                        <option value="<?php echo $bill['id']; ?>" 
                                                data-amount="<?php echo $balance; ?>"
                                                data-customer="<?php echo htmlspecialchars($bill['customer_name']); ?>"
                                                data-credit-balance="<?php echo $bill['credit_balance']; ?>"
                                                data-outstanding-balance="<?php echo $bill['outstanding_balance']; ?>">
                                            <?php echo htmlspecialchars($bill['invoiceid'] . ' - ' . $bill['customer_name'] . 
                                                     ' (Balance: ₱' . number_format($balance, 2) . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Customer Information -->
                            <div class="mb-3">
                                <label class="form-label">Customer</label>
                                <input type="text" class="form-control" id="customer_name" readonly>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <small class="text-success" id="credit_balance"></small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-danger" id="outstanding_balance"></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Amount -->
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" name="amount" id="amount" 
                                           step="0.01" required>
                                </div>
                                <div class="form-text" id="balance_text"></div>
                            </div>

                            <!-- Payment Method -->
                            <div class="mb-3">
                                <label for="payment_method_id" class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method_id" required>
                                    <option value="">Select payment method...</option>
                                    <?php while ($method = $payment_methods->fetch_assoc()): ?>
                                        <option value="<?php echo $method['id']; ?>">
                                            <?php echo htmlspecialchars($method['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Reference Number -->
                            <div class="mb-3">
                                <label for="reference_no" class="form-label">Reference Number</label>
                                <input type="text" class="form-control" name="reference_no" 
                                       placeholder="Enter reference number">
                            </div>

                            <!-- Payment Date -->
                            <div class="mb-3">
                                <label for="payment_date" class="form-label">Payment Date</label>
                                <input type="date" class="form-control" name="payment_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <!-- Notes -->
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="3" 
                                          placeholder="Enter any additional notes"></textarea>
                            </div>

                            <div class="text-end">
                                <a href="payments.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#billing_id').on('change', function() {
        const selected = $(this).find('option:selected');
        const amount = selected.data('amount');
        const customer = selected.data('customer');
        const creditBalance = selected.data('credit-balance');
        const outstandingBalance = selected.data('outstanding-balance');
        
        $('#customer_name').val(customer);
        $('#amount').attr('max', amount);
        $('#balance_text').text(`Maximum amount: ₱${amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        
        if (creditBalance > 0) {
            $('#credit_balance').html(`<i class="bx bx-plus-circle"></i> Available Credit: ₱${creditBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        } else {
            $('#credit_balance').html('');
        }
        
        if (outstandingBalance > 0) {
            $('#outstanding_balance').html(`<i class="bx bx-minus-circle"></i> Outstanding Balance: ₱${outstandingBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
        } else {
            $('#outstanding_balance').html('');
        }
    });

    $('#paymentForm').on('submit', function(e) {
        const amount = parseFloat($('#amount').val());
        const maxAmount = parseFloat($('#billing_id option:selected').data('amount'));
        
        if (amount > maxAmount) {
            e.preventDefault();
            alert('Payment amount cannot exceed the remaining balance.');
            return false;
        }
    });
});
</script>

<?php include 'footer.php'; ?>