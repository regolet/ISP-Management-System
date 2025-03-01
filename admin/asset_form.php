<?php
require_once 'config.php';
check_login();

$page_title = isset($_GET['id']) ? 'Edit Asset' : 'Add Asset';
$_SESSION['active_menu'] = 'assets';

// Initialize asset data
$asset = [
    'id' => '',
    'name' => '',
    'description' => '',
    'address' => '',
    'expected_amount' => '',
    'collection_frequency' => 'monthly',
    'next_collection_date' => date('Y-m-d'),
    'notes' => '',
    'status' => 'active'
];

$is_edit = false;

if (isset($_GET['id'])) {
    $id = clean_input($_GET['id']);
    $is_edit = true;
    
    $stmt = $conn->prepare("SELECT * FROM assets WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $asset = $result->fetch_assoc();
    } else {
        $_SESSION['error'] = "Asset not found";
        header("Location: assets.php");
        exit();
    }
}

include 'header.php';
include 'navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include 'alerts.php'; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2"><?php echo $page_title; ?></h1>
            <a href="assets.php" class="btn btn-secondary">
                <i class='bx bx-arrow-back'></i> Back to Assets
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="asset_save.php" method="POST" class="needs-validation" novalidate>
                    <?php if ($asset['id']): ?>
                        <input type="hidden" name="id" value="<?php echo $asset['id']; ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Asset Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" 
                                   value="<?php echo htmlspecialchars($asset['name']); ?>" required>
                            <div class="invalid-feedback">Please enter asset name</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Expected Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="text" class="form-control" name="expected_amount" 
                                       value="<?php echo number_format((float)$asset['expected_amount'], 2); ?>" 
                                       pattern="[0-9]*\.?[0-9]+" required>
                                <div class="invalid-feedback">Please enter a valid amount (numbers only)</div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars($asset['description']); ?></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="address" rows="2" required><?php echo htmlspecialchars($asset['address']); ?></textarea>
                            <div class="invalid-feedback">Please enter address</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Collection Frequency <span class="text-danger">*</span></label>
                            <select class="form-select" name="collection_frequency" required>
                                <option value="daily" <?php echo $asset['collection_frequency'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo $asset['collection_frequency'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?php echo $asset['collection_frequency'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="quarterly" <?php echo $asset['collection_frequency'] == 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                <option value="annually" <?php echo $asset['collection_frequency'] == 'annually' ? 'selected' : ''; ?>>Annually</option>
                            </select>
                            <div class="invalid-feedback">Please select collection frequency</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Next Collection Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="next_collection_date" 
                                   value="<?php echo $asset['next_collection_date']; ?>" required>
                            <div class="invalid-feedback">Please select next collection date</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($asset['notes']); ?></textarea>
                        </div>

                        <?php if ($is_edit): ?>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" <?php echo $asset['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $asset['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $is_edit ? 'Update' : 'Create'; ?> Asset
                        </button>
                        <a href="assets.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

// Update next collection date based on frequency
document.querySelector('select[name="collection_frequency"]').addEventListener('change', function() {
    const nextDateInput = document.querySelector('input[name="next_collection_date"]');
    const currentDate = new Date();
    
    switch(this.value) {
        case 'daily':
            currentDate.setDate(currentDate.getDate() + 1);
            break;
        case 'weekly':
            currentDate.setDate(currentDate.getDate() + 7);
            break;
        case 'monthly':
            currentDate.setMonth(currentDate.getMonth() + 1);
            break;
        case 'quarterly':
            currentDate.setMonth(currentDate.getMonth() + 3);
            break;
        case 'annually':
            currentDate.setFullYear(currentDate.getFullYear() + 1);
            break;
    }
    
    nextDateInput.value = currentDate.toISOString().split('T')[0];
});
</script>

<?php include 'footer.php'; ?> 