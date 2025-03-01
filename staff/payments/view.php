<?php
require_once '../../config.php';
require_once '../staff_auth.php';

$page_title = "View Payment";
$_SESSION['active_menu'] = 'payments';

// Get payment details
$payment_id = clean_input($_GET['id']);
$stmt = $conn->prepare("
    SELECT p.*, 
           c.name as customer_name, c.address as customer_address,
           b.invoiceid, b.amount as bill_amount, b.due_date,
           e.first_name, e.last_name
    FROM payments p
    LEFT JOIN customers c ON p.customer_id = c.id
    LEFT JOIN billing b ON p.billing_id = b.id
    LEFT JOIN employees e ON p.created_by = e.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) {
    $_SESSION['error'] = "Payment not found";
    header("Location: list.php");
    exit();
}

include '../../header.php';
include '../staff_navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Payment Details</h1>
            <div class="btn-toolbar gap-2">
                <button class="btn btn-primary" onclick="printPayment()">
                    <i class='bx bx-printer'></i> Print
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Back to List
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body" id="printArea">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Payment Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td width="150"><strong>Payment ID:</strong></td>
                                <td>#<?php echo $payment['id']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Amount:</strong></td>
                                <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Payment Date:</strong></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($payment['payment_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Method:</strong></td>
                                <td><?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Reference:</strong></td>
                                <td><?php echo htmlspecialchars($payment['reference_number'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $payment['status'] === 'completed' ? 'success' : 'warning'; 
                                    ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Customer Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td width="150"><strong>Name:</strong></td>
                                <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Address:</strong></td>
                                <td><?php echo htmlspecialchars($payment['customer_address']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Bill Number:</strong></td>
                                <td><?php echo htmlspecialchars($payment['invoiceid']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Bill Amount:</strong></td>
                                <td>₱<?php echo number_format($payment['bill_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Due Date:</strong></td>
                                <td><?php echo date('M d, Y', strtotime($payment['due_date'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <h5>Additional Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td width="150"><strong>Notes:</strong></td>
                                <td><?php echo nl2br(htmlspecialchars($payment['notes'] ?? 'No notes')); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Recorded By:</strong></td>
                                <td>
                                    <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                    on <?php echo date('M d, Y h:i A', strtotime($payment['created_at'])); ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printPayment() {
    var printContents = document.getElementById('printArea').innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
}
</script>

<?php include '../../footer.php'; ?>
