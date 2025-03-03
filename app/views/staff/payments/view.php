<?php
$title = 'Payment Details - ISP Management System';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Payment Details</h2>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group">
            <?php if ($payment['receipt_number']): ?>
                <a href="/staff/payments/generate-receipt/<?= $payment['id'] ?>" class="btn btn-primary">
                    <i class="fa fa-download"></i> Download Receipt
                </a>
            <?php endif; ?>
            <a href="/staff/payments" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Payments
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Payment Information -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Payment Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Receipt Number</th>
                                <td>
                                    <?php if ($payment['receipt_number']): ?>
                                        <span class="font-monospace">
                                            <?= htmlspecialchars($payment['receipt_number']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Payment Date</th>
                                <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                            </tr>
                            <tr>
                                <th>Amount</th>
                                <td class="fw-bold"><?= formatCurrency($payment['amount']) ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge bg-<?= getStatusBadgeClass($payment['status']) ?>">
                                        <?= ucfirst($payment['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Payment Type</th>
                                <td><?= htmlspecialchars($payment['payment_type_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Payment Method</th>
                                <td><?= htmlspecialchars($payment['payment_method_name']) ?></td>
                            </tr>
                            <tr>
                                <th>Reference #</th>
                                <td>
                                    <?= htmlspecialchars($payment['reference_number']) ?: 
                                        '<span class="text-muted">-</span>' ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Processed By</th>
                                <td>
                                    <?= htmlspecialchars($payment['processed_by_name']) ?: 
                                        '<span class="text-muted">Pending</span>' ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if ($payment['description']): ?>
                    <div class="mt-3">
                        <h6>Description</h6>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($payment['description'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($payment['remarks']): ?>
                    <div class="mt-3">
                        <h6>Additional Notes</h6>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($payment['remarks'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Supporting Documents -->
        <?php if (!empty($attachments)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Supporting Documents</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($attachments as $attachment): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fa <?= getFileIcon($attachment['file_type']) ?> me-2"></i>
                                    <?= htmlspecialchars($attachment['file_name']) ?>
                                    <small class="text-muted ms-2">
                                        (<?= formatFileSize($attachment['file_size']) ?>)
                                    </small>
                                </div>
                                <a href="/staff/payments/download-attachment/<?= $attachment['id'] ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fa fa-download"></i> Download
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Payment Status Timeline -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payment Status Timeline</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($statusHistory as $status): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-<?= getStatusBadgeClass($status['status']) ?>"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1"><?= ucfirst($status['status']) ?></h6>
                                    <small class="text-muted">
                                        <?= date('M d, Y H:i', strtotime($status['created_at'])) ?>
                                    </small>
                                </div>
                                <?php if ($status['remarks']): ?>
                                    <p class="mb-0 text-muted">
                                        <?= htmlspecialchars($status['remarks']) ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($status['updated_by']): ?>
                                    <small class="text-muted">
                                        By: <?= htmlspecialchars($status['updated_by']) ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($payment['status'] === 'pending'): ?>
                    <div class="mt-3">
                        <button type="button" class="btn btn-danger w-100 cancel-payment" 
                                data-id="<?= $payment['id'] ?>">
                            <i class="fa fa-times"></i> Cancel Payment Request
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function getStatusBadgeClass($status) {
    return match ($status) {
        'processed' => 'success',
        'pending' => 'warning',
        'approved' => 'info',
        'rejected' => 'danger',
        default => 'secondary'
    };
}

function getFileIcon($fileType) {
    return match (strtolower($fileType)) {
        'application/pdf' => 'fa-file-pdf',
        'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
        'image/jpeg', 'image/png', 'image/gif' => 'fa-file-image',
        default => 'fa-file'
    };
}

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}
?>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    position: relative;
    padding-left: 30px;
    margin-bottom: 20px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.timeline-content {
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 5px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #dee2e6;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cancel Payment Handler
    const cancelButton = document.querySelector('.cancel-payment');
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to cancel this payment request?')) {
                const paymentId = this.dataset.id;
                
                fetch(`/staff/payments/${paymentId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= \App\Middleware\CSRFMiddleware::getToken() ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to cancel payment request');
                    }
                });
            }
        });
    }
});
</script>
