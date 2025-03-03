<?php
// Database connection parameters
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'isp';

try {
    // Create connection with error handling
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "Connected successfully\n";

    // Helper function for query execution with error handling
    function executeQuery($conn, $query, $description) {
        try {
            if ($conn->query($query)) {
                echo "\n✓ Success: $description";
                return true;
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            echo "\n✗ Failed: $description - " . $e->getMessage();
            return false;
        }
    }

    // Get table dependencies
    function getTableDependencies($conn) {
        $dependencies = [];
        $result = $conn->query("
            SELECT 
                TABLE_NAME,
                REFERENCED_TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE 
                TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY TABLE_NAME
        ");

        while ($row = $result->fetch_assoc()) {
            if (!isset($dependencies[$row['TABLE_NAME']])) {
                $dependencies[$row['TABLE_NAME']] = [];
            }
            $dependencies[$row['TABLE_NAME']][] = $row['REFERENCED_TABLE_NAME'];
        }

        return $dependencies;
    }

    // Sort tables based on dependencies
    function sortTablesByDependency($dependencies) {
        $sorted = [];
        $visited = [];

        foreach (array_keys($dependencies) as $table) {
            if (!isset($visited[$table])) {
                sortTablesDFS($table, $dependencies, $visited, $sorted);
            }
        }

        return array_reverse($sorted);
    }

    function sortTablesDFS($table, $dependencies, &$visited, &$sorted) {
        $visited[$table] = true;

        if (isset($dependencies[$table])) {
            foreach ($dependencies[$table] as $dep) {
                if (!isset($visited[$dep])) {
                    sortTablesDFS($dep, $dependencies, $visited, $sorted);
                }
            }
        }

        $sorted[] = $table;
    }

    try {
        $conn->begin_transaction();

        // Disable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");

        echo "\nStarting database reset...\n";

        // Get and sort tables by dependency
        $dependencies = getTableDependencies($conn);
        $tables = sortTablesByDependency($dependencies);

        // Add any missing tables that don't have foreign keys
        $allTables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $allTables[] = $row[0];
        }
        $tables = array_unique(array_merge($tables, $allTables));

        // Delete data from tables in correct order
        foreach ($tables as $table) {
            executeQuery($conn, "DELETE FROM `$table`", "Deleting data from $table table");
        }

        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        // Reinsert default data
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        // Insert default roles
        executeQuery($conn, "INSERT INTO roles (name, description) VALUES
            ('Admin', 'Full system access with all privileges'),
            ('Staff', 'General staff access for day-to-day operations'),
            ('collector', 'Access to payment collection and related features'),
            ('customer', 'Limited access for customers to view their own data')", 
            "Creating default roles");

        // Insert default permissions
        executeQuery($conn, "INSERT INTO permissions (name, description, category) VALUES
            ('view_dashboard', 'Can view dashboard', 'menu'),
            ('manage_dashboard', 'Can customize dashboard widgets', 'action'),
            ('view_users', 'Can view user list', 'menu'),
            ('add_user', 'Can add new users', 'action'),
            ('edit_user', 'Can edit users', 'action'),
            ('delete_user', 'Can delete users', 'action'),
            ('view_customers', 'Can view customer list', 'menu'),
            ('add_customer', 'Can add new customers', 'action'),
            ('edit_customer', 'Can edit customers', 'action'),
            ('delete_customer', 'Can delete customers', 'action'),
            ('view_billing', 'Can view billing section', 'menu'),
            ('process_payments', 'Can process payments', 'action')",
            "Inserting default permissions");

        // Insert default admin user
        executeQuery($conn, "INSERT INTO users (username, password, email, role, status) VALUES
            ('admin', '$default_password', 'admin@example.com', 'admin', 'active')", 
            "Creating default admin user");

        // Insert default company settings
        executeQuery($conn, "INSERT INTO company (setting_key, setting_value, company_name, company_address, company_phone, company_email, currency) VALUES
            ('company_profile', 'default', 'Your Company Name', 'Your Company Address', 'Your Phone', 'your@email.com', 'PHP')",
            "Inserting default company settings");

        // Insert default settings
        executeQuery($conn, "INSERT INTO settings (category, name, value, type, description) VALUES 
            ('company', 'company_name', 'Your Company Name', 'text', 'Name of your company'),
            ('company', 'company_address', 'Your Company Address', 'text', 'Company physical address'),
            ('company', 'company_phone', 'Your Phone Number', 'text', 'Company contact number'),
            ('company', 'company_email', 'your@email.com', 'text', 'Company email address'),
            ('company', 'company_website', 'www.yourcompany.com', 'text', 'Company website URL'),
            ('financial', 'tax_rate', '0', 'number', 'Default tax rate'),
            ('financial', 'currency', 'PHP', 'text', 'Default currency'),
            ('financial', 'late_fee_percentage', '0', 'number', 'Late payment fee percentage'),
            ('financial', 'grace_period_days', '3', 'number', 'Grace period for payments'),
            ('system', 'enable_email_notifications', '0', 'boolean', 'Enable/disable email notifications'),
            ('system', 'enable_sms_notifications', '0', 'boolean', 'Enable/disable SMS notifications'),
            ('system', 'maintenance_mode', '0', 'boolean', 'Enable/disable maintenance mode'),
            ('system', 'default_pagination', '20', 'number', 'Default items per page')", 
            "Inserting default settings");

        // Insert default payment methods
        executeQuery($conn, "INSERT INTO payment_methods (name, description, status) VALUES
            ('Cash', 'Direct cash payment', 'active'),
            ('Bank Transfer', 'Bank transfer payment', 'active'),
            ('Credit Card', 'Credit card payment', 'active'),
            ('GCash', 'GCash mobile payment', 'active'),
            ('Maya', 'Maya digital payment', 'active')", 
            "Inserting default payment methods");

        // Insert default leave types
        executeQuery($conn, "INSERT INTO leave_types (name, description, paid) VALUES
            ('Vacation Leave', 'Annual vacation leave', 1),
            ('Sick Leave', 'Medical leave', 1),
            ('Emergency Leave', 'Urgent personal matters', 1),
            ('Maternity Leave', 'Pregnancy related leave', 1),
            ('Paternity Leave', 'New father leave', 1)", 
            "Inserting default leave types");

        // Insert default expense categories
        executeQuery($conn, "INSERT INTO expense_categories (name, description, is_active) VALUES
            ('Utilities', 'Utility bills and services', 1),
            ('Rent', 'Rental and lease payments', 1),
            ('Salaries', 'Employee salaries and wages', 1),
            ('Equipment', 'Equipment purchases and rentals', 1),
            ('Maintenance', 'Maintenance and repairs', 1),
            ('Marketing', 'Marketing and advertising', 1),
            ('Office Supplies', 'Office materials and supplies', 1),
            ('Others', 'Other miscellaneous expenses', 1)",
            "Inserting default expense categories");

        // Insert default plans
        executeQuery($conn, "INSERT INTO plans (name, description, amount, bandwidth, status) VALUES
            ('Basic Plan', 'Basic internet service', 1000.00, '10', 'active'),
            ('Standard Plan', 'Standard internet service', 1500.00, '20', 'active'),
            ('Premium Plan', 'Premium internet service', 2000.00, '50', 'active')",
            "Inserting default plans");

        $conn->commit();
        echo "\nDatabase reset completed successfully!\n";
        echo "All data has been deleted and default records have been reinserted.\n";

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo "\nError during reset: " . $e->getMessage() . "\n";
} finally {
    // Re-enable foreign key checks if script fails
    if (isset($conn)) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->close();
    }
}
?>
