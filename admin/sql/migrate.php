<?php
require_once '../../config.php';

class FTTHDatabaseMigration {
    private $db;
    private $schemaVersion = 1; // Increment this when adding new migrations
    private $migrations = [];

    public function __construct($db) {
        $this->db = $db;
        $this->initMigrationsTable();
        $this->registerMigrations();
    }

    private function initMigrationsTable() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS olt_migrations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                version INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_version (version)
            )
        ");
    }

    private function registerMigrations() {
        // Register all migrations here
        $this->migrations = [
            1 => [
                'name' => 'Initial Schema',
                'up' => function() {
                    $schema = file_get_contents(__DIR__ . '/ftth_schema.sql');
                    $queries = array_filter(array_map('trim', explode(';', $schema)));
                    
                    foreach ($queries as $query) {
                        if (!empty($query)) {
                            $this->db->exec($query);
                        }
                    }
                }
            ],
            // Add new migrations here as needed
            /*
            2 => [
                'name' => 'Add Client Support',
                'up' => function() {
                    $this->db->exec("
                        CREATE TABLE IF NOT EXISTS olt_clients (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            nap_id INT NOT NULL,
                            port_no INT NOT NULL,
                            name VARCHAR(100) NOT NULL,
                            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (nap_id) REFERENCES olt_naps(id) ON DELETE CASCADE,
                            UNIQUE KEY unique_nap_port (nap_id, port_no)
                        )
                    ");
                }
            }
            */
        ];
    }

    public function getCurrentVersion() {
        try {
            $stmt = $this->db->query("
                SELECT COALESCE(MAX(version), 0) 
                FROM olt_migrations
            ");
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function migrate() {
        $currentVersion = $this->getCurrentVersion();
        
        if ($currentVersion >= $this->schemaVersion) {
            echo "Database is up to date (version $currentVersion).\n";
            return;
        }

        try {
            $this->db->beginTransaction();

            echo "Current database version: $currentVersion\n";
            echo "Target version: {$this->schemaVersion}\n\n";
            echo "Executing migrations...\n";
            echo str_repeat("-", 50) . "\n";

            for ($version = $currentVersion + 1; $version <= $this->schemaVersion; $version++) {
                if (!isset($this->migrations[$version])) {
                    throw new Exception("Migration version $version not found");
                }

                $migration = $this->migrations[$version];
                echo "Running migration $version: {$migration['name']}\n";

                // Execute migration
                $migration['up']();

                // Record migration
                $stmt = $this->db->prepare("
                    INSERT INTO olt_migrations (version, name)
                    VALUES (?, ?)
                ");
                $stmt->execute([$version, $migration['name']]);

                echo "✓ Migration $version completed\n";
            }

            $this->db->commit();
            echo str_repeat("-", 50) . "\n";
            echo "All migrations completed successfully.\n";
            echo "Current database version: {$this->schemaVersion}\n";

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo "Error during migration: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    public function status() {
        $currentVersion = $this->getCurrentVersion();
        
        echo "\nFTTH Database Migration Status\n";
        echo str_repeat("=", 50) . "\n\n";
        echo "Current Version: $currentVersion\n";
        echo "Latest Version: {$this->schemaVersion}\n\n";
        
        if ($currentVersion < $this->schemaVersion) {
            echo "Pending Migrations:\n";
            echo str_repeat("-", 50) . "\n";
            
            for ($version = $currentVersion + 1; $version <= $this->schemaVersion; $version++) {
                if (isset($this->migrations[$version])) {
                    echo sprintf("Version %d: %s\n", 
                        $version, 
                        $this->migrations[$version]['name']
                    );
                }
            }
        } else {
            echo "Database is up to date.\n";
        }

        // Show executed migrations
        $stmt = $this->db->query("
            SELECT version, name, executed_at 
            FROM olt_migrations 
            ORDER BY version DESC
        ");
        
        echo "\nExecuted Migrations:\n";
        echo str_repeat("-", 50) . "\n";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("Version %d: %s (executed: %s)\n",
                $row['version'],
                $row['name'],
                $row['executed_at']
            );
        }
        echo "\n";
    }
}

// Handle command line arguments
if ($argc < 2) {
    echo "Usage: php migrate.php <command>\n";
    echo "Commands:\n";
    echo "  status   Show migration status\n";
    echo "  migrate  Run pending migrations\n";
    exit(1);
}

$migration = new FTTHDatabaseMigration($db);

switch ($argv[1]) {
    case 'status':
        $migration->status();
        break;
    
    case 'migrate':
        $migration->migrate();
        break;
    
    default:
        echo "Unknown command: {$argv[1]}\n";
        exit(1);
}
