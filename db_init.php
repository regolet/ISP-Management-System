<?php
// Load initialization file
require_once __DIR__ . '/app/init.php';

// Initialize database connection
require_once __DIR__ . '/config/database.php';
$database = new Database();

// Get database connection
$db = $database->getConnection();

// Check if database connection was successful
if ($db === null) {
    error_log("Failed to establish database connection in db_init.php");
    die("Database connection failed. Please check the database configuration.");
}

// Force database initialization to ensure all tables are created
try {
    $result = $database->initializeDatabase(true);
    if (!$result) {
        error_log("Database initialization failed in db_init.php");
    }
} catch (Exception $e) {
    error_log("Exception during database initialization: " . $e->getMessage());
}

// Check if plans table exists and has data
try {
    $query = "SELECT COUNT(*) as count FROM plans";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Insert sample plans if table is empty
        $db->exec("
        INSERT OR IGNORE INTO plans (name, description, speed_mbps, price, billing_cycle, is_active) VALUES 
        ('Basic', 'Basic internet plan for everyday browsing', 10, 29.99, 'monthly', 1),
        ('Standard', 'Standard internet plan for families', 50, 49.99, 'monthly', 1),
        ('Premium', 'Premium high-speed internet for gamers and streamers', 100, 79.99, 'monthly', 1),
        ('Business', 'Business-grade internet with priority support', 200, 129.99, 'monthly', 1)
        ");
    }
} catch (Exception $e) {
    // Table might not exist yet, which is fine as it will be created during initialization
    error_log("Plans table check: " . $e->getMessage());
}

echo "Database initialization complete.\n";