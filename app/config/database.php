<?php
namespace App\Config;

// Add this line to reference the global PDO class
use PDO;
use PDOException;
use Exception;

class Database {
    private $db_path;
    public $conn = null;
    private static $instance = null;

    private function __construct() {
        // Set the SQLite database file path
        $this->db_path = dirname(dirname(__DIR__)) . '/database/isp-management.sqlite';
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Get database connection with proper error handling and parameter binding support
     * @return PDO
     */
    public function getConnection() {
        try {
            if ($this->conn === null) {
                // Create the database directory if it doesn't exist
                $db_dir = dirname($this->db_path);
                if (!file_exists($db_dir)) {
                    mkdir($db_dir, 0755, true);
                }

                // Create a new PDO connection to SQLite
                $this->conn = new PDO(
                    "sqlite:" . $this->db_path,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false // Important: This makes PDO treat numeric parameters correctly
                    ]
                );
                
                // Enable foreign keys support in SQLite
                $this->conn->exec('PRAGMA foreign_keys = ON');
            }
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection error. Please try again later.");
        }
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollBack();
    }

    /**
     * Execute query with proper parameter binding
     * @param string $query
     * @param array $params
     * @return PDOStatement
     */
    public function executeQuery($query, $params = []) {
        try {
            // Convert MySQL syntax to SQLite syntax
            $query = $this->convertMySQLToSQLite($query);
            
            $stmt = $this->conn->prepare($query);
            
            // Bind each parameter with its proper type
            foreach ($params as $key => $value) {
                if (is_int($value)) {
                    $stmt->bindValue(is_numeric($key) ? $key + 1 : $key, $value, PDO::PARAM_INT);
                } else if (is_bool($value)) {
                    $stmt->bindValue(is_numeric($key) ? $key + 1 : $key, $value, PDO::PARAM_BOOL);
                } else if (is_null($value)) {
                    $stmt->bindValue(is_numeric($key) ? $key + 1 : $key, $value, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue(is_numeric($key) ? $key + 1 : $key, $value, PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Execution Error: " . $e->getMessage());
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }

    /**
     * Convert MySQL syntax to SQLite syntax
     * @param string $query
     * @return string
     */
    private function convertMySQLToSQLite($query) {
        // Replace AUTO_INCREMENT with AUTOINCREMENT
        $query = preg_replace('/AUTO_INCREMENT/i', 'AUTOINCREMENT', $query);
        
        // Replace ENGINE=InnoDB with nothing
        $query = preg_replace('/ENGINE=InnoDB/i', '', $query);
        
        // Replace UNSIGNED with nothing (SQLite doesn't support UNSIGNED)
        $query = preg_replace('/UNSIGNED/i', '', $query);
        
        // Replace INT with INTEGER
        $query = preg_replace('/\bINT\b/i', 'INTEGER', $query);
        
        // Replace SHOW TABLES LIKE with SELECT name FROM sqlite_master WHERE type='table' AND name=
        if (preg_match('/SHOW TABLES LIKE/i', $query)) {
            $table = preg_replace("/SHOW TABLES LIKE '(.*)'/i", '$1', $query);
            $query = "SELECT name FROM sqlite_master WHERE type='table' AND name='" . $table . "'";
        }
        
        // Replace DESCRIBE with PRAGMA table_info
        if (preg_match('/DESCRIBE/i', $query)) {
            $table = preg_replace("/DESCRIBE (.*)/i", '$1', $query);
            $query = "PRAGMA table_info(" . $table . ")";
        }
        
        // Replace SHOW CREATE TABLE with SELECT sql FROM sqlite_master WHERE type='table' AND name=
        if (preg_match('/SHOW CREATE TABLE/i', $query)) {
            $table = preg_replace("/SHOW CREATE TABLE (.*)/i", '$1', $query);
            $query = "SELECT sql FROM sqlite_master WHERE type='table' AND name='" . $table . "'";
        }
        
        // Replace MySQL date functions with SQLite equivalents
        $query = preg_replace('/YEAR\(([^)]+)\)/i', "strftime('%Y', $1)", $query);
        $query = preg_replace('/MONTH\(([^)]+)\)/i', "strftime('%m', $1)", $query);
        $query = preg_replace('/DAY\(([^)]+)\)/i', "strftime('%d', $1)", $query);
        $query = preg_replace('/CURRENT_DATE/i', "'now'", $query);
        
        return $query;
    }

    /**
     * Get last inserted ID
     * @return string
     */
    public function getLastInsertId() {
        return $this->conn->lastInsertId();
    }

    /**
     * Quote string
     * @param string $string
     * @return string
     */
    public function quote($string) {
        return $this->conn->quote($string);
    }

    /**
     * Check if table exists
     * @param string $table
     * @return bool
     */
    public function tableExists($table) {
        try {
            $result = $this->conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'");
            return $result->rowCount() > 0;
        } catch(PDOException $e) {
            error_log("Table Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get table columns
     * @param string $table
     * @return array
     */
    public function getColumns($table) {
        try {
            $stmt = $this->conn->query("PRAGMA table_info({$table})");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['name'];
            }
            return $columns;
        } catch(PDOException $e) {
            error_log("Get Columns Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create backup of database
     * @param string $path
     * @return bool
     */
    public function backup($path) {
        try {
            // For SQLite, we can simply copy the database file
            if (file_exists($this->db_path)) {
                return copy($this->db_path, $path);
            }
            return false;
        } catch(Exception $e) {
            error_log("Backup Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Initialize the database with schema if it doesn't exist
     * @param bool $force Force recreation of tables even if they exist
     */
    public function initializeDatabase($force = false) {
        try {
            // Check if the database file exists
            $db_exists = file_exists($this->db_path);
            
            // Get connection
            $conn = $this->getConnection();
            
            // If database doesn't exist, is empty, or force is true
            if (!$db_exists || !$this->tableExists('users') || $force) {
                // Read the SQL schema file
                $sqlFile = dirname(dirname(__DIR__)) . '/database/sqlite_schema.sql';
                
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    
                    // Split SQL into statements
                    $sqlStatements = array_filter(
                        array_map(
                            'trim',
                            explode(';', $sql)
                        ),
                        'strlen'
                    );
                    
                    // Execute each statement
                    foreach ($sqlStatements as $statement) {
                        // Convert MySQL syntax to SQLite syntax
                        $statement = $this->convertMySQLToSQLite($statement);
                        if (!empty($statement)) {
                            $conn->exec($statement);
                        }
                    }
                    
                    return true;
                } else {
                    error_log("Schema file not found: " . $sqlFile);
                    return false;
                }
            }
            
            return true;
        } catch(PDOException $e) {
            error_log("Database Initialization Error: " . $e->getMessage());
            return false;
        }
    }
}