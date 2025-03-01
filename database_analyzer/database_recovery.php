<?php
// Database connection parameters
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'isp';

class DatabaseRecovery {
    private $conn;
    private $dbname;
    private $tables_created = 0;
    private $tables_modified = 0;
    private $errors = [];

    public function __construct($host, $user, $pass, $dbname) {
        $this->dbname = $dbname;
        try {
            $this->conn = new mysqli($host, $user, $pass);
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Create and select database
            $this->conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
            $this->conn->select_db($dbname);
            
            echo "Connected successfully to $dbname\n";
        } catch (Exception $e) {
            die("Connection error: " . $e->getMessage() . "\n");
        }
    }

    private function executeQuery($query, $description) {
        try {
            if ($this->conn->query($query)) {
                echo "\n✓ Success: $description";
                return true;
            } else {
                throw new Exception($this->conn->error);
            }
        } catch (Exception $e) {
            $this->errors[] = "$description - " . $e->getMessage();
            echo "\n✗ Failed: $description - " . $e->getMessage();
            return false;
        }
    }

    private function tableExists($tableName) {
        $result = $this->conn->query("SHOW TABLES LIKE '$tableName'");
        return $result->num_rows > 0;
    }

    private function columnExists($tableName, $columnName) {
        $result = $this->conn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        return $result->num_rows > 0;
    }

    public function recover() {
        try {
            $this->conn->begin_transaction();

            echo "\nStarting database structure verification...\n";
            echo "This may take a few minutes...\n";

            // Create tables in correct dependency order
            $this->createTables();
            
            // Insert default data
            $this->insertDefaultData();

            $this->conn->commit();
            
            echo "\n=== Recovery Summary ===\n";
            echo "Tables created: {$this->tables_created}\n";
            echo "Tables modified: {$this->tables_modified}\n";
            echo "Errors encountered: " . count($this->errors) . "\n";
            
            if (!empty($this->errors)) {
                echo "\nError Details:\n";
                foreach ($this->errors as $error) {
                    echo "- $error\n";
                }
            }
            
            echo "\nDatabase structure verification completed!\n";

        } catch (Exception $e) {
            $this->conn->rollback();
            echo "\nCritical error during recovery: " . $e->getMessage() . "\n";
        }
    }

    private function createTables() {
        // Disable foreign key checks temporarily
        $this->conn->query("SET FOREIGN_KEY_CHECKS = 0");

        // Users and Authentication
        $this->executeQuery("CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `email` varchar(255) DEFAULT NULL,
            `role` enum('admin','staff','collector','customer') NOT NULL,
            `status` enum('active','inactive') DEFAULT 'active',
            `last_login` datetime DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `employee_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            KEY `idx_username` (`username`),
            KEY `idx_role` (`role`),
            KEY `idx_status` (`status`),
            KEY `employee_id` (`employee_id`)
        ) ENGINE=InnoDB", "Creating users table");

        // Roles and Permissions
        $this->executeQuery("CREATE TABLE IF NOT EXISTS `roles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB", "Creating roles table");

        $this->executeQuery("CREATE TABLE IF NOT EXISTS `permissions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `category` enum('menu','action') NOT NULL DEFAULT 'action',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`),
            KEY `idx_category` (`category`)
        ) ENGINE=InnoDB", "Creating permissions table");

        $this->executeQuery("CREATE TABLE IF NOT EXISTS `role_permissions` (
            `role_id` int(11) NOT NULL,
            `permission_id` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`role_id`,`permission_id`),
            KEY `permission_id` (`permission_id`),
            CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
            CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB", "Creating role_permissions table");

        // Customer Management
        $this->executeQuery("CREATE TABLE IF NOT EXISTS `customers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `customer_code` varchar(20) DEFAULT NULL,
            `name` varchar(255) NOT NULL,
            `address` text DEFAULT NULL,
            `contact` varchar(50) DEFAULT NULL,
            `contact_number` varchar(50) DEFAULT NULL,
            `email` varchar(255) DEFAULT NULL,
            `plan_id` int(11) DEFAULT NULL,
            `installation_date` date DEFAULT NULL,
            `due_date` date DEFAULT NULL,
            `status` enum('active','inactive','suspended') DEFAULT 'active',
            `coordinates` point DEFAULT NULL,
            `service_area_id` int(11) DEFAULT NULL,
            `installation_fee` decimal(10,2) DEFAULT 0.00,
            `installation_notes` text DEFAULT NULL,
            `balance` decimal(10,2) DEFAULT 0.00,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `credit_balance` decimal(10,2) DEFAULT 0.00,
            `outstanding_balance` decimal(10,2) DEFAULT 0.00,
            PRIMARY KEY (`id`),
            UNIQUE KEY `customer_code` (`customer_code`),
            KEY `idx_customer_code` (`customer_code`),
            KEY `idx_status` (`status`),
            KEY `idx_plan` (`plan_id`),
            KEY `idx_due_date` (`due_date`),
            KEY `user_id` (`user_id`),
            KEY `service_area_id` (`service_area_id`)
        ) ENGINE=InnoDB", "Creating customers table");

        // Plans and Subscriptions
        $this->executeQuery("CREATE TABLE IF NOT EXISTS `plans` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text DEFAULT NULL,
            `amount` decimal(10,2) NOT NULL,
            `bandwidth` varchar(50) DEFAULT NULL,
            `setup_fee` decimal(10,2) DEFAULT 0.00,
            `contract_duration` int(11) DEFAULT 0,
            `download_speed` varchar(50) DEFAULT NULL,
            `upload_speed` varchar(50) DEFAULT NULL,
            `data_cap` bigint(20) DEFAULT 0,
            `status` enum('active','inactive') DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB", "Creating plans table");

        $this->executeQuery("CREATE TABLE IF NOT EXISTS `subscriptions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `plan_id` int(11) NOT NULL,
            `start_date` date NOT NULL,
            `end_date` date DEFAULT NULL,
            `billing_cycle` enum('monthly','quarterly','annually') DEFAULT 'monthly',
            `status` enum('active','inactive','suspended','cancelled') DEFAULT 'active',
            `notes` text DEFAULT NULL,
            `auto_renew` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `customer_id` (`customer_id`),
            KEY `plan_id` (`plan_id`),
            KEY `idx_status` (`status`),
            CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
            CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
        ) ENGINE=InnoDB", "Creating subscriptions table");

        // Billing and Payments
        $this->executeQuery("CREATE TABLE IF NOT EXISTS `billing` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `invoiceid` varchar(50) DEFAULT NULL,
            `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
            `status` enum('unpaid','paid','partial','overdue','cancelled','pending') DEFAULT 'unpaid',
            `due_date` date NOT NULL,
            `billtocustomer` varchar(255) DEFAULT NULL,
            `billingaddress` text DEFAULT NULL,
            `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
            `companyname` varchar(255) DEFAULT NULL,
            `companyaddress` text DEFAULT NULL,
            `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
            `late_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `created_by` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `invoiceid` (`invoiceid`),
            KEY `idx_customer` (`customer_id`),
            KEY `idx_status` (`status`),
            KEY `idx_due_date` (`due_date`),
            KEY `idx_created_at` (`created_at`),
            KEY `created_by` (`created_by`)
        ) ENGINE=InnoDB", "Creating billing table");

        // Add more table creation statements...
        // (The rest of the tables would follow the same pattern)

        // Re-enable foreign key checks
        $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    private function insertDefaultData() {
        // Insert default admin user if not exists
        $result = $this->conn->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
        if ($result->num_rows == 0) {
            $default_password = password_hash('admin123', PASSWORD_DEFAULT);
            $this->executeQuery(
                "INSERT INTO users (username, password, email, role, status) 
                VALUES ('admin', '$default_password', 'admin@example.com', 'admin', 'active')",
                "Creating default admin user"
            );
        }

        // Insert default roles if they don't exist
        $this->executeQuery(
            "INSERT IGNORE INTO roles (name, description) VALUES
            ('admin', 'Full system access with all privileges'),
            ('staff', 'General staff access for day-to-day operations'),
            ('customer', 'Limited access for customers to view their own data')",
            "Inserting default roles"
        );

        // Insert default settings if they don't exist
        $this->executeQuery(
            "INSERT IGNORE INTO settings (category, name, value, type, description) VALUES
            ('company', 'company_name', 'Your Company', 'text', 'Company name'),
            ('company', 'company_email', 'company@example.com', 'text', 'Company email'),
            ('system', 'maintenance_mode', '0', 'boolean', 'Maintenance mode status')",
            "Inserting default settings"
        );
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Execute recovery
$recovery = new DatabaseRecovery($host, $user, $pass, $dbname);
$recovery->recover();
?>
