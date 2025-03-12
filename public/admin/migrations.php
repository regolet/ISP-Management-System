<?php
require_once dirname(__DIR__, 2) . '/app/init.php';
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/views/layouts/sidebar.php';
require_once dirname(__DIR__, 2) . '/app/controllers/AuthController.php';

// Ensure the Plan model is loaded
if (file_exists(dirname(__DIR__, 2) . '/app/Models/Plan.php')) {
    require_once dirname(__DIR__, 2) . '/app/Models/Plan.php';
} elseif (file_exists(dirname(__DIR__, 2) . '/app/models/Plan.php')) {
    require_once dirname(__DIR__, 2) . '/app/models/Plan.php';
}

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    header("Location: /login.php");
    exit();
}

// Check if user has admin role
if (!$auth->hasRole('admin')) {
    header("Location: /dashboard.php?error=unauthorized");
    exit();
}

$output = '';
$messageType = 'info';

// Run migrations if requested
if (isset($_POST['run_migrations']) && $_POST['run_migrations'] === 'yes') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $output = 'Invalid CSRF token';
        $messageType = 'danger';
    } else {
        try {
            // Capture output
            ob_start();
            
            // Get database connection
            $database = new Database();
            $db = $database->getConnection();
            
            echo "Running migrations...\n\n";
            
            // Create plans table
            echo "Creating plans table...\n";
            $db->exec("
            CREATE TABLE IF NOT EXISTS plans (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                description TEXT,
                speed_mbps INTEGER NOT NULL,
                price REAL NOT NULL,
                setup_fee REAL DEFAULT 0,
                billing_cycle TEXT NOT NULL DEFAULT 'monthly',
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            
            -- Add index for faster lookups
            CREATE INDEX IF NOT EXISTS idx_plans_name ON plans(name);
            CREATE INDEX IF NOT EXISTS idx_plans_is_active ON plans(is_active);
            ");
            echo "Plans table created successfully.\n\n";
            
            // Check if client_subscriptions table exists
            $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='client_subscriptions'");
            $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tableExists) {
                echo "Creating client_subscriptions table...\n";
                $db->exec("
                CREATE TABLE IF NOT EXISTS client_subscriptions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    client_id INTEGER,
                    plan_name TEXT NOT NULL,
                    speed_mbps INTEGER NOT NULL,
                    price REAL NOT NULL,
                    subscription_number TEXT NOT NULL UNIQUE,
                    status TEXT NOT NULL DEFAULT 'active',
                    ip_address TEXT,
                    start_date DATE NOT NULL,
                    end_date DATE,
                    billing_cycle TEXT NOT NULL DEFAULT 'monthly',
                    identifier TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );
                
                -- Add indexes for faster lookups
                CREATE INDEX IF NOT EXISTS idx_subscriptions_client_id ON client_subscriptions(client_id);
                CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON client_subscriptions(status);
                CREATE INDEX IF NOT EXISTS idx_subscriptions_number ON client_subscriptions(subscription_number);
                ");
                echo "Client subscriptions table created successfully.\n\n";
            } else {
                echo "Client subscriptions table already exists.\n\n";
            }
            
            // Insert sample plans
            echo "Inserting sample plans...\n";
            $db->exec("
            INSERT OR IGNORE INTO plans (name, description, speed_mbps, price, billing_cycle, is_active) VALUES 
            ('Basic', 'Basic internet plan for everyday browsing', 10, 29.99, 'monthly', 1),
            ('Standard', 'Standard internet plan for families', 50, 49.99, 'monthly', 1),
            ('Premium', 'Premium high-speed internet for gamers and streamers', 100, 79.99, 'monthly', 1),
            ('Business', 'Business-grade internet with priority support', 200, 129.99, 'monthly', 1)
            ");
            echo "Sample plans inserted successfully.\n\n";
            
            echo "Migrations completed successfully!\n";
            
            $output = ob_get_clean();
            $messageType = 'success';
        } catch (Exception $e) {
            $output = 'Error running migrations: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migrations - ISP Management System</title>

    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Render Sidebar -->
    <?php renderSidebar('database'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Mobile Toggle Button -->
        <button type="button" id="sidebarToggle" class="btn btn-link d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1040;">
            <i class="fas fa-bars"></i>
        </button>

        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Database Migrations</h1>
                <a href="/admin/check_database.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Database Tools
                </a>
            </div>

            <?php if (!empty($output)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <pre class="mb-0"><?php echo htmlspecialchars($output); ?></pre>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Migrations Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Run Database Migrations</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">
                        This tool will run database migrations to update the database structure. The following migrations will be applied:
                    </p>
                    
                    <ul class="mb-4">
                        <li>Create plans table (if it doesn't exist)</li>
                        <li>Create client_subscriptions table (if it doesn't exist)</li>
                        <li>Insert sample plans data</li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Make sure you have a backup of your database before running migrations.
                    </div>
                    
                    <form method="POST" action="" class="mt-4">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        <input type="hidden" name="run_migrations" value="yes">
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-database me-2"></i>Run Migrations
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Initialize global configuration -->
    <script>
        // Initialize global configuration
        window.APP_CONFIG = {
            baseUrl: <?php echo json_encode(rtrim((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2), '/')); ?>,
            csrfToken: <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>,
            userId: <?php echo json_encode($_SESSION['user_id'] ?? ''); ?>,
            userRole: <?php echo json_encode($_SESSION['role'] ?? ''); ?>
        };
    </script>
    
    <!-- Custom JavaScript -->
    <script src="/assets/js/sidebar.js"></script>
</body>
</html>