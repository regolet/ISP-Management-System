<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'isp');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

$is_cli = php_sapi_name() === 'cli';
function output($message) {
    global $is_cli;
    if ($is_cli) {
        echo $message . "\n";
    } else {
        echo "<p>$message</p>";
    }
}

output("Setting up Inventory System...");

$sql_queries = [
    // Create categories table
    "CREATE TABLE IF NOT EXISTS categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        active BOOLEAN DEFAULT 1
    )" => "Creating categories table",

    // Create suppliers table
    "CREATE TABLE IF NOT EXISTS suppliers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        contact_person VARCHAR(100),
        phone VARCHAR(20),
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        active BOOLEAN DEFAULT 1
    )" => "Creating suppliers table",

    // Create products table
    "CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        category_id INT,
        code VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        unit VARCHAR(20) NOT NULL,
        quantity INT DEFAULT 0,
        reorder_level INT DEFAULT 0,
        cost_price DECIMAL(10,2) DEFAULT 0.00,
        selling_price DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )" => "Creating products table",

    // Create inventory_transactions table
    "CREATE TABLE IF NOT EXISTS inventory_transactions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        product_id INT,
        supplier_id INT,
        type ENUM('in', 'out') NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2),
        total_price DECIMAL(10,2),
        reference_no VARCHAR(50),
        notes TEXT,
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by INT,
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
        FOREIGN KEY (created_by) REFERENCES users(id)
    )" => "Creating inventory_transactions table",

    // Insert sample categories
    "INSERT IGNORE INTO categories (name, description) VALUES 
    ('Network Equipment', 'Routers, switches, and other networking devices'),
    ('Cables', 'Various types of network cables'),
    ('Accessories', 'Network accessories and tools')" => "Adding sample categories",

    // Insert sample suppliers
    "INSERT IGNORE INTO suppliers (name, contact_person, phone, email, address) VALUES 
    ('Network Solutions Inc.', 'John Smith', '123-456-7890', 'john@networksolutions.com', '123 Network St.'),
    ('Cable Masters', 'Jane Doe', '098-765-4321', 'jane@cablemasters.com', '456 Cable Ave.'),
    ('Tech Supplies Co.', 'Mike Johnson', '555-123-4567', 'mike@techsupplies.com', '789 Tech Blvd.')" => "Adding sample suppliers"
];

$success_count = 0;
$error_messages = [];

// Start transaction
$conn->begin_transaction();

try {
    foreach ($sql_queries as $sql => $description) {
        output($description . "...");
        if ($conn->query($sql)) {
            output(" Success!");
            $success_count++;
        } else {
            throw new Exception($conn->error);
        }
    }
    
    // If all queries successful, commit transaction
    $conn->commit();
    output("\nDatabase setup completed successfully! ($success_count queries executed)");
    
    if (!$is_cli) {
        echo "<p><a href='inventory.php' class='btn btn-primary'>Go to Inventory</a></p>";
    }
    
} catch (Exception $e) {
    // If error occurs, rollback changes
    $conn->rollback();
    output("Error setting up database: " . $e->getMessage());
}

$conn->close();
?>
