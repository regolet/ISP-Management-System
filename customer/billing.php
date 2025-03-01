<?php
require_once __DIR__ . '/../config.php';
check_auth('customer');

// Get customer data
$customer_query = "SELECT * FROM customers WHERE user_id = ?";
$stmt = $conn->prepare($customer_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

// Get billing history
$billing_query = "SELECT b.*, 
                        COALESCE(SUM(p.amount), 0) as total_paid
                 FROM billing b 
                 LEFT JOIN payments p ON b.id = p.billing_id AND p.status = 'completed'
                 WHERE b.customer_id = ? 
                 GROUP BY b.id
                 ORDER BY b.created_at DESC";
$stmt = $conn->prepare($billing_query);
$stmt->bind_param("i", $customer['id']);
$stmt->execute();
$billing_result = $stmt->get_result();

$page_title = 'Billing History';
include __DIR__ . '/../header.php';
include __DIR__ . '/navbar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Billing History</h1>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Your Bills</h6>
            </div>
            <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Invoice ID</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($bill = $billing_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bill['invoiceid']); ?></td>
                                <td>₱<?php echo number_format($bill['amount'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($bill['due_date'])); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch($bill['status']) {
                                        case 'paid':
                                            $status_class = 'text-success';
                                            break;
                                        case 'unpaid':
                                            $status_class = 'text-danger';
                                            break;
                                        case 'overdue':
                                            $status_class = 'text-warning';
                                            break;
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>">
                                        <?php echo ucfirst($bill['status']); ?>
                                    </span>
                                </td>
                                <td>₱<?php echo number_format($bill['balance'], 2); ?></td>
                                <td>
                                    <?php if ($bill['status'] !== 'paid'): ?>
                                        <a href="payments.php?bill_id=<?php echo $bill['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            Pay Now
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-info"
                                            onclick="viewBillDetails(<?php echo $bill['id']; ?>)">
                                        Details
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bill Details Modal -->
<div class="modal fade" id="billDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bill Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="billDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewBillDetails(billId) {
    // Load bill details via AJAX
    $.get('get_bill_details.php', { id: billId }, function(data) {
        $('#billDetailsContent').html(data);
        $('#billDetailsModal').modal('show');
    });
}
</script>

<?php include __DIR__ . '/../footer.php'; ?>