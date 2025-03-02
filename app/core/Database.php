<?php
namespace App\Core;

class Database extends \mysqli 
{
    private static $instance = null;
    private $config;

    public function __construct() 
    {
        // Get database configuration
        $this->config = require APP_ROOT . '/config/database.php';
        $config = $this->config['connections'][$this->config['default']];

        // Connect to database
        parent::__construct(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            $config['port'] ?? 3306
        );

        // Set charset and collation
        $this->set_charset($config['charset']);

        // Check connection
        if ($this->connect_error) {
            throw new \Exception('Database connection failed: ' . $this->connect_error);
        }

        // Set options
        foreach ($config['options'] as $option => $value) {
            $this->options($option, $value);
        }

        // Store instance
        self::$instance = $this;
    }

    /**
     * Get database instance (singleton)
     */
    public static function getInstance() 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Begin transaction with retry
     */
    public function beginTransaction() 
    {
        $maxAttempts = $this->config['retry']['max_attempts'];
        $delay = $this->config['retry']['delay'];
        $multiplier = $this->config['retry']['multiplier'];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                if (parent::begin_transaction()) {
                    return true;
                }
            } catch (\Exception $e) {
                if ($attempt === $maxAttempts) {
                    throw $e;
                }
                usleep($delay * 1000);
                $delay *= $multiplier;
            }
        }

        return false;
    }

    /**
     * Execute query with retry
     * @return \mysqli_result|bool
     */
    #[\ReturnTypeWillChange]
    public function query($query, $resultmode = MYSQLI_STORE_RESULT) 
    {
        $maxAttempts = $this->config['retry']['max_attempts'];
        $delay = $this->config['retry']['delay'];
        $multiplier = $this->config['retry']['multiplier'];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $start = microtime(true);
                $result = parent::query($query, $resultmode);
                $duration = microtime(true) - $start;

                // Log slow queries
                if ($this->config['query_log']['enabled'] && 
                    $duration * 1000 > $this->config['query_log']['threshold']) {
                    $this->logQuery($query, $duration);
                }

                return $result;

            } catch (\Exception $e) {
                if ($attempt === $maxAttempts) {
                    $this->logError($e->getMessage(), $query);
                    throw $e;
                }
                usleep($delay * 1000);
                $delay *= $multiplier;
            }
        }

        return false;
    }

    /**
     * Log slow query
     */
    private function logQuery($query, $duration) 
    {
        $logPath = $this->config['query_log']['path'];
        $message = sprintf(
            "[%s] Duration: %.2fms | Query: %s\n",
            date('Y-m-d H:i:s'),
            $duration * 1000,
            $query
        );

        error_log($message, 3, $logPath);
    }

    /**
     * Log database error
     */
    private function logError($error, $query = null) 
    {
        if (!$this->config['error_log']['enabled']) {
            return;
        }

        $logPath = $this->config['error_log']['path'];
        $message = sprintf(
            "[%s] Error: %s | Query: %s\n",
            date('Y-m-d H:i:s'),
            $error,
            $query
        );

        error_log($message, 3, $logPath);
    }

    /**
     * Prepare statement with retry
     * @return \mysqli_stmt|false
     */
    #[\ReturnTypeWillChange]
    public function prepare($query) 
    {
        $maxAttempts = $this->config['retry']['max_attempts'];
        $delay = $this->config['retry']['delay'];
        $multiplier = $this->config['retry']['multiplier'];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $stmt = parent::prepare($query);
                if ($stmt) {
                    return $stmt;
                }
            } catch (\Exception $e) {
                if ($attempt === $maxAttempts) {
                    $this->logError($e->getMessage(), $query);
                    throw $e;
                }
                usleep($delay * 1000);
                $delay *= $multiplier;
            }
        }

        return false;
    }

    /**
     * Get last error
     */
    public function getError() 
    {
        return $this->error;
    }

    /**
     * Get last error number
     */
    public function getErrorNo() 
    {
        return $this->errno;
    }

    /**
     * Check if connection is alive
     */
    public function isConnected() 
    {
        return $this->ping();
    }

    /**
     * Reconnect if connection is lost
     */
    public function reconnect() 
    {
        $this->close();
        $this->connect(
            $this->config['host'],
            $this->config['username'],
            $this->config['password'],
            $this->config['database'],
            $this->config['port'] ?? 3306
        );
    }

    /**
     * Get database configuration
     */
    public function getConfig() 
    {
        return $this->config;
    }
}
