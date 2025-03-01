<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

$page_title = "Add Expense";
$_SESSION['active_menu'] = 'expenses';

// Get expense categories
$categories = $conn->query("SELECT * FROM expense_categories ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        $category_id = $_POST['category_id'];
        $amount = floatval($_POST['amount']);
        $description = $_POST['description'];
        $expense_date = $_POST['expense_date'];
        $payment_method = $_POST['payment_method'];
        $reference_number = $_POST['reference_number'] ?? null;
        
        // Validate inputs
        if ($amount <= 0) {
            throw new Exception("Amount must be greater than 0");
        }

        // Insert expense record
        $stmt = $conn->prepare("
            INSERT INTO expenses (
                category_id, user_id, amount, description, expense_date,
                payment_method, reference_number, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->bind_param("iidsssss", 
            $category_id,
            $_SESSION['user_id'],
            $amount,
            $description,
            $expense_date,
            $payment_method,
            $reference_number
        );

        // Handle receipt upload if present
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/receipts/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('receipt_') . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $file_path)) {
                $stmt = $conn->prepare("UPDATE expenses SET receipt_image = ? WHERE id = LAST_INSERT_ID()");
                $stmt->bind_param("s", $file_name);
                $stmt->execute();
            }
        }

        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success'] = "Expense recorded successfully";
            
            // Log activity
            log_activity($_SESSION['user_id'], 'expense_added', 
                "Added expense of ₱" . number_format($amount, 2));
            
            header("Location: list.php");
            exit();
        } else {
            throw new Exception("Failed to record expense");
        }

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
                <h1 class="h3 mb-0">Add New Expense</h1>
            </div>
            <div class="col-md-6 text-end">
                <a href="list.php" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Back to List
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control" name="amount" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="expense_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="gcash">GCash</option>
                                <option value="maya">Maya</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference_number">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Receipt Image</label>
                            <input type="file" class="form-control" name="receipt" 
                                   accept="image/jpeg,image/png,image/jpg">
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Submit Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Add form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const amount = parseFloat(this.querySelector('[name="amount"]').value);
    if (amount <= 0) {
        e.preventDefault();
        alert('Amount must be greater than 0');
    }
});
</script>

<?php include '../../footer.php'; ?>
