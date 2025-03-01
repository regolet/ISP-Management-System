<?php
require_once 'config.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: inventory.php");
    exit();
}

$errors = [];

// Get and clean form data
$code = clean_input($_POST['code']);
$name = clean_input($_POST['name']);
$category_id = clean_input($_POST['category_id']);
$description = clean_input($_POST['description']);
$unit = clean_input($_POST['unit']);
$reorder_level = clean_input($_POST['reorder_level']);
$cost_price = clean_input($_POST['cost_price']);
$selling_price = clean_input($_POST['selling_price']);

// Validate required fields
if (empty($code)) $errors[] = "Product code is required";
if (empty($name)) $errors[] = "Name is required";
if (empty($category_id)) $errors[] = "Category is required";
if (empty($unit)) $errors[] = "Unit is required";
if (!is_numeric($reorder_level)) $errors[] = "Reorder level must be a number";
if (!is_numeric($cost_price)) $errors[] = "Cost price must be a number";
if (!is_numeric($selling_price)) $errors[] = "Selling price must be a number";

// Check if code is unique
$stmt = $conn->prepare("SELECT id FROM products WHERE code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $errors[] = "Product code already exists";
}

if (empty($errors)) {
    // Insert product
    $stmt = $conn->prepare("
        INSERT INTO products (
            code, name, category_id, description, unit, 
            reorder_level, cost_price, selling_price, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    
    $stmt->bind_param("ssissddd", 
        $code,
        $name,
        $category_id,
        $description,
        $unit,
        $reorder_level,
        $cost_price,
        $selling_price
    );
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product added successfully";
        header("Location: inventory.php");
        exit();
    } else {
        $errors[] = "Error: " . $conn->error;
    }
}

if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
    // Store form data in session to repopulate form
    $_SESSION['form_data'] = $_POST;
    header("Location: inventory.php");
    exit();
}
?>
