<?php
require_once 'config.php';
check_login();

$product = [
    'id' => '',
    'code' => '',
    'name' => '',
    'category_id' => '',
    'description' => '',
    'unit' => '',
    'quantity' => '0',
    'reorder_level' => '0',
    'cost_price' => '0.00',
    'selling_price' => '0.00',
    'status' => 'active'
];

$is_edit = false;
$page_title = isset($_GET['id']) ? "Edit Product" : "Add New Product";
$_SESSION['active_menu'] = 'inventory';

include 'header.php';
include 'navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="h2"><?php echo $page_title; ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="inventory.php" class="btn btn-secondary">
                    <i class="bx bx-arrow-back"></i> Back to Inventory
                </a>
            </div>
        </div>

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product['code'] = clean_input($_POST['code']);
    $product['name'] = clean_input($_POST['name']);
    $product['category_id'] = clean_input($_POST['category_id']);
    $product['description'] = clean_input($_POST['description']);
    $product['unit'] = clean_input($_POST['unit']);
    $product['reorder_level'] = clean_input($_POST['reorder_level']);
    $product['cost_price'] = clean_input($_POST['cost_price']);
    $product['selling_price'] = clean_input($_POST['selling_price']);
    $product['status'] = clean_input($_POST['status']);
    
    $errors = [];
    
    // Validate required fields
    if (empty($product['code'])) $errors[] = "Product code is required";
    if (empty($product['name'])) $errors[] = "Name is required";
    if (empty($product['category_id'])) $errors[] = "Category is required";
    if (empty($product['unit'])) $errors[] = "Unit is required";
    
    // Check if code is unique
    $stmt = $conn->prepare("SELECT id FROM products WHERE code = ? AND id != ?");
    $stmt->bind_param("si", $product['code'], $product['id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Product code already exists";
    }
    
    if (empty($errors)) {
        if ($is_edit) {
            $stmt = $conn->prepare("UPDATE products SET 
                code = ?, name = ?, category_id = ?, description = ?, 
                unit = ?, reorder_level = ?, cost_price = ?, 
                selling_price = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssissdddsi", 
                $product['code'],
                $product['name'],
                $product['category_id'],
                $product['description'],
                $product['unit'],
                $product['reorder_level'],
                $product['cost_price'],
                $product['selling_price'],
                $product['status'],
                $product['id']
            );
        } else {
            $stmt = $conn->prepare("INSERT INTO products (
                code, name, category_id, description, unit, 
                reorder_level, cost_price, selling_price, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissddds", 
                $product['code'],
                $product['name'],
                $product['category_id'],
                $product['description'],
                $product['unit'],
                $product['reorder_level'],
                $product['cost_price'],
                $product['selling_price'],
                $product['status']
            );
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = ($is_edit ? "Product updated" : "Product added") . " successfully";
            header("Location: inventory.php");
            exit();
        } else {
            $errors[] = "Error: " . $conn->error;
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

include 'header.php';
?>

<body>
    <?php include 'navbar.php'; ?>
    
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $title; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="inventory.php" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Back to Inventory
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo $error; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Code</label>
                                <input type="text" class="form-control" name="code" 
                                       value="<?php echo htmlspecialchars($product['code']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php
                                while ($category = $categories->fetch_assoc()) {
                                    $selected = $category['id'] == $product['category_id'] ? 'selected' : '';
                                    echo "<option value='{$category['id']}' {$selected}>{$category['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Unit</label>
                                <input type="text" class="form-control" name="unit" 
                                       value="<?php echo htmlspecialchars($product['unit']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" name="reorder_level" 
                                       value="<?php echo htmlspecialchars($product['reorder_level']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="active" <?php echo $product['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $product['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cost Price</label>
                                <input type="number" class="form-control" name="cost_price" step="0.01"
                                       value="<?php echo htmlspecialchars($product['cost_price']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Selling Price</label>
                                <input type="number" class="form-control" name="selling_price" step="0.01"
                                       value="<?php echo htmlspecialchars($product['selling_price']); ?>" required>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <a href="inventory.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <?php echo $is_edit ? 'Update' : 'Add'; ?> Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>
