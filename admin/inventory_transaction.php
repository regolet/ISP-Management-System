<?php
require_once 'config.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: inventory.php");
    exit();
}

$type = clean_input($_POST['type']);
$product_id = clean_input($_POST['product_id']);
$quantity = clean_input($_POST['quantity']);
$unit_price = clean_input($_POST['unit_price']);
$reference_no = clean_input($_POST['reference_no']);
$notes = clean_input($_POST['notes']);
$supplier_id = isset($_POST['supplier_id']) ? clean_input($_POST['supplier_id']) : null;

$errors = [];

// Validate required fields
if (empty($product_id)) $errors[] = "Product is required";
if (empty($quantity)) $errors[] = "Quantity is required";
if (empty($unit_price)) $errors[] = "Unit price is required";
if (empty($reference_no)) $errors[] = "Reference number is required";
if ($type === 'in' && empty($supplier_id)) $errors[] = "Supplier is required";

if (empty($errors)) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get current product quantity
        $stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Product not found");
        }
        
        $current_quantity = $result->fetch_assoc()['quantity'];
        
        // Calculate new quantity
        $new_quantity = $type === 'in' ? 
            $current_quantity + $quantity : 
            $current_quantity - $quantity;
        
        // Check if we have enough stock for stock out
        if ($type === 'out' && $new_quantity < 0) {
            throw new Exception("Insufficient stock");
        }
        
        // Update product quantity
        $stmt = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $product_id);
        $stmt->execute();
        
        // Record transaction
        $total_price = $quantity * $unit_price;
        $stmt = $conn->prepare("INSERT INTO inventory_transactions (
            product_id, supplier_id, type, quantity, unit_price, 
            total_price, reference_no, notes, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisiddssi", 
            $product_id,
            $supplier_id,
            $type,
            $quantity,
            $unit_price,
            $total_price,
            $reference_no,
            $notes,
            $_SESSION['user_id']
        );
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "Stock " . ($type === 'in' ? "in" : "out") . " recorded successfully";
        header("Location: inventory.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $errors[] = "Error: " . $e->getMessage();
    }
}

if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
    header("Location: inventory.php");
    exit();
}
