<?php
require_once 'config.php';
check_login();

$id = isset($_GET['id']) ? clean_input($_GET['id']) : 0;
$expense = null;
$categories = $conn->query("SELECT * FROM expense_categories ORDER BY name");

if ($id) {
    $stmt = $conn->prepare("
        SELECT e.*, ec.name as category_name
        FROM expenses e
        LEFT JOIN expense_categories ec ON e.category_id = ec.id
        WHERE e.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $expense = $stmt->get_result()->fetch_assoc();

    // Check if user can edit this expense
    if (!$expense ||
        ($expense['status'] !== 'pending' ||
         ($expense['user_id'] !== $_SESSION['user_id'] && $_SESSION['role'] !== 'admin'))) {
        header("Location: expenses.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = clean_input($_POST['category_id']);
    $amount = clean_input($_POST['amount']);
    $description = clean_input($_POST['description']);
    $expense_date = clean_input($_POST['expense_date']);
    $payment_method = clean_input($_POST['payment_method']);
    $reference_number = clean_input($_POST['reference_number']);

    $errors = [];

    // Validate inputs
    if (empty($category_id)) {
        $errors[] = "Please select a category";
    }
    if (empty($amount) || !is_numeric($amount)) {
        $errors[] = "Please enter a valid amount";
    }
    if (empty($description)) {
        $errors[] = "Please enter a description";
    }
    if (empty($expense_date)) {
        $errors[] = "Please select an expense date";
    }
    if (empty($payment_method)) {
        $errors[] = "Please select a payment method";
    }

    // Handle file upload
    $receipt_image = '';
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['receipt']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid file type. Allowed types: " . implode(', ', $allowed);
        } else {
            $upload_dir = 'uploads/receipts/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $new_filename = uniqid() . '.' . $ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $destination)) {
                $receipt_image = $destination;
            } else {
                $errors[] = "Error uploading file";
            }
        }
    }

    if (empty($errors)) {
        if ($id) {
            $stmt = $conn->prepare("
                UPDATE expenses SET
                    category_id = ?,
                    amount = ?,
                    description = ?,
                    expense_date = ?,
                    payment_method = ?,
                    reference_number = ?,
                    receipt_image = COALESCE(?, receipt_image)
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->bind_param(
                "idsssssi",
                $category_id,
                $amount,
                $description,
                $expense_date,
                $payment_method,
                $reference_number,
                $receipt_image,
                $id
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO expenses (
                    category_id, user_id, amount, description,
                    expense_date, payment_method, reference_number,
                    receipt_image, status, created_at
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, 'pending', NOW()
                )
            ");
            $stmt->bind_param(
                "iidsssss",
                $category_id,
                $_SESSION['user_id'],
                $amount,
                $description,
                $expense_date,
                $payment_method,
                $reference_number,
                $receipt_image
            );
        }

        if ($stmt->execute()) {
            $expense_id = $id ?: $stmt->insert_id;
            log_activity(
                $_SESSION['user_id'],
                $id ? 'update_expense' : 'add_expense',
                ($id ? "Updated" : "Added") . " expense of ₱" . number_format($amount, 2)
            );
            header("Location: expense_view.php?id=$expense_id&success=1");
            exit;
        } else {
            $errors[] = "Error saving expense: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Expense - ISP Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .content-wrapper {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 40px);
        }
        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
                padding: 15px;
            }
            .container-fluid {
                padding: 0;
            }
            .card {
                border-radius: 0;
                margin: -15px;
            }
            .card-header {
                border-radius: 0 !important;
            }
            .form-group {
                margin-bottom: 1.5rem;
            }
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 1rem;
        }
        .card-body {
            padding: 1.5rem;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #495057;
        }
        .form-control, .form-select {
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid #ced4da;
            font-size: 1rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .input-group-text {
            background-color: #e9ecef;
            border-color: #ced4da;
            padding: 0.75rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }
        .form-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .alert-danger {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .alert-danger ul {
            margin-bottom: 0;
            padding-left: 1.25rem;
        }
        @media (min-width: 769px) {
            .btn {
                width: auto;
                margin-bottom: 0;
                margin-left: 0.5rem;
            }
            .container-fluid {
                padding-left: 0;
                max-width: 100%;
            }
            .row {
                margin-left: 0;
            }
            .col-12 {
                padding-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?php echo $id ? 'Edit' : 'Add'; ?> Expense</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <div class="form-group">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php while ($category = $categories->fetch_assoc()): ?>
                                            <option value="<?php echo $category['id']; ?>"
                                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ||
                                                               (isset($expense['category_id']) && $expense['category_id'] == $category['id'])
                                                               ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a category</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" name="amount" class="form-control" step="0.01" required
                                               value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) :
                                                        (isset($expense['amount']) ? $expense['amount'] : ''); ?>">
                                    </div>
                                    <div class="invalid-feedback">Please enter a valid amount</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3" required><?php
                                        echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) :
                                             (isset($expense['description']) ? htmlspecialchars($expense['description']) : ''); ?></textarea>
                                    <div class="invalid-feedback">Please enter a description</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Expense Date</label>
                                    <input type="date" name="expense_date" class="form-control" required
                                           value="<?php echo isset($_POST['expense_date']) ? htmlspecialchars($_POST['expense_date']) :
                                                    (isset($expense['expense_date']) ? $expense['expense_date'] : date('Y-m-d')); ?>">
                                    <div class="invalid-feedback">Please select the expense date</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Payment Method</label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="cash" selected>Cash</option>
                                        <option value="bank_transfer" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'bank_transfer') ||
                                                                                     (isset($expense['payment_method']) && $expense['payment_method'] === 'bank_transfer')
                                                                                     ? 'selected' : ''; ?>>Bank Transfer</option>
                                        <option value="credit_card" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'credit_card') ||
                                                                                     (isset($expense['payment_method']) && $expense['payment_method'] === 'credit_card')
                                                                                     ? 'selected' : ''; ?>>Credit Card</option>
                                        <option value="gcash" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'gcash') ||
                                                                                 (isset($expense['payment_method']) && $expense['payment_method'] === 'gcash')
                                                                                 ? 'selected' : ''; ?>>GCash</option>
                                        <option value="maya" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'maya') ||
                                                                               (isset($expense['payment_method']) && $expense['payment_method'] === 'maya')
                                                                               ? 'selected' : ''; ?>>Maya</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a payment method</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" name="reference_number" class="form-control"
                                           value="<?php echo isset($_POST['reference_number']) ? htmlspecialchars($_POST['reference_number']) :
                                                    (isset($expense['reference_number']) ? htmlspecialchars($expense['reference_number']) : ''); ?>"
                                           placeholder="Optional for cash payments">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Receipt Image</label>
                                    <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                    <div class="form-text">Supported formats: JPG, JPEG, PNG, PDF</div>
                                    <?php if (isset($expense['receipt_image']) && $expense['receipt_image']): ?>
                                        <div class="mt-2">
                                            <a href="<?php echo htmlspecialchars($expense['receipt_image']); ?>"
                                               target="_blank" class="btn btn-sm btn-info text-white">
                                                View Current Receipt
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="text-end mt-4">
                                    <a href="expenses.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $id ? 'Update' : 'Add'; ?> Expense
                                    </button>
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
        // Enable Bootstrap form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>