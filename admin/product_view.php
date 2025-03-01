<?php
require_once 'config.php';
check_login();

if (!isset($_GET['id'])) {
    header("Location: inventory.php");
    exit();
}

$id = clean_input($_GET['id']);

// Get product details
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Product not found";
    header("Location: inventory.php");
    exit();
}

$product = $result->fetch_assoc();

// Get transaction history
$stmt = $conn->prepare("
    SELECT t.*, s.name as supplier_name, u.username as user_name
    FROM inventory_transactions t
    LEFT JOIN suppliers s ON t.supplier_id = s.id
    LEFT JOIN users u ON t.created_by = u.id
    WHERE t.product_id = ?
    ORDER BY t.transaction_date DESC
    LIMIT 50
");
$stmt->bind_param("i", $id);
$stmt->execute();
$transactions = $stmt->get_result();

$page_title = "View Product: " . $product['name'];
include 'header.php';
?>

<body>
    <?php include 'navbar.php'; ?>
    
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Product Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="inventory.php" class="btn btn-secondary me-2">
                        <i class="bx bx-arrow-back"></i> Back to Inventory
                    </a>
                    <a href="product_form.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                        <i class="bx bx-edit"></i> Edit Product
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="35%">Product Code</th>
                                    <td><?php echo htmlspecialchars($product['code']); ?></td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Category</th>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td><?php echo nl2br(htmlspecialchars($product['description'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Stock Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="35%">Current Stock</th>
                                    <td>
                                        <?php echo number_format($product['quantity']); ?> <?php echo htmlspecialchars($product['unit']); ?>
                                        <?php if ($product['quantity'] <= $product['reorder_level']): ?>
                                            <span class="badge bg-danger">Low Stock</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Reorder Level</th>
                                    <td><?php echo number_format($product['reorder_level']); ?> <?php echo htmlspecialchars($product['unit']); ?></td>
                                </tr>
                                <tr>
                                    <th>Unit</th>
                                    <td><?php echo htmlspecialchars($product['unit']); ?></td>
                                </tr>
                                <tr>
                                    <th>Cost Price</th>
                                    <td>₱<?php echo number_format($product['cost_price'], 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Selling Price</th>
                                    <td>₱<?php echo number_format($product['selling_price'], 2); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Transaction History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total Price</th>
                                    <th>Reference No</th>
                                    <th>Supplier</th>
                                    <th>Notes</th>
                                    <th>Created By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($transaction = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['type'] === 'in' ? 'success' : 'warning'; ?>">
                                            <?php echo $transaction['type'] === 'in' ? 'Stock In' : 'Stock Out'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($transaction['quantity']); ?></td>
                                    <td>₱<?php echo number_format($transaction['unit_price'], 2); ?></td>
                                    <td>₱<?php echo number_format($transaction['total_price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['reference_no']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['supplier_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['notes']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>
