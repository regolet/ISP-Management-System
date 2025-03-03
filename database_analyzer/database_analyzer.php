<?php
namespace App\DatabaseAnalyzer;

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Config;
use App\Core\Database;

class DatabaseAnalyzer 
{
    private $db;
    private $config;
    private $logPath;
    private $backupPath;

    public function __construct() 
    {
        // Load configuration
        $this->config = Config::getInstance();
        
        // Initialize paths
        $this->logPath = dirname(__DIR__) . '/storage/logs/analyzer';
        $this->backupPath = dirname(__DIR__) . '/storage/backups/database';
        
        // Ensure directories exist
        if (!file_exists($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        // Initialize database connection
        $this->db = Database::getInstance($this->config);
    }

    /**
     * Analyze database structure
     */
    public function analyzeTables(): array 
    {
        try {
            $results = [];
            $query = "SELECT 
                        TABLE_NAME, 
                        ENGINE, 
                        TABLE_ROWS, 
                        DATA_LENGTH, 
                        INDEX_LENGTH,
                        UPDATE_TIME,
                        TABLE_COLLATION
                    FROM information_schema.TABLES 
                    WHERE TABLE_SCHEMA = ?";

            $dbName = $this->config->get('database.name');
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('s', $dbName);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $results[$row['TABLE_NAME']] = [
                    'engine' => $row['ENGINE'],
                    'rows' => $row['TABLE_ROWS'],
                    'data_size' => $this->formatBytes($row['DATA_LENGTH']),
                    'index_size' => $this->formatBytes($row['INDEX_LENGTH']),
                    'last_update' => $row['UPDATE_TIME'],
                    'collation' => $row['TABLE_COLLATION']
                ];
            }

            $this->logAnalysis('tables', $results);
            return $results;

        } catch (\Exception $e) {
            $this->logError('table_analysis', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check database health
     */
    public function checkHealth(): array 
    {
        try {
            $issues = [];
            
            // Check table status
            $query = "SHOW TABLE STATUS";
            $result = $this->db->query($query)->get();
            
            foreach ($result as $table) {
                // Check for MyISAM tables
                if ($table['Engine'] === 'MyISAM') {
                    $issues[] = "Table '{$table['Name']}' uses MyISAM engine";
                }
                
                // Check for non-UTF8 tables
                if (!strstr($table['Collation'], 'utf8')) {
                    $issues[] = "Table '{$table['Name']}' uses non-UTF8 collation";
                }
            }

            // Check for fragmentation
            $query = "SELECT TABLE_NAME, DATA_FREE 
                     FROM information_schema.TABLES 
                     WHERE TABLE_SCHEMA = ? AND DATA_FREE > 0";
            
            $dbName = $this->config->get('database.name');
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('s', $dbName);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                if ($row['DATA_FREE'] > 1024 * 1024) { // More than 1MB fragmentation
                    $issues[] = "Table '{$row['TABLE_NAME']}' is fragmented";
                }
            }

            $this->logAnalysis('health_check', $issues);
            return $issues;

        } catch (\Exception $e) {
            $this->logError('health_check', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create database backup
     */
    public function createBackup(): string 
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = $this->backupPath . "/backup_{$timestamp}.sql";
            
            // Use mysqldump through shell_exec with proper escaping
            $command = sprintf(
                'mysqldump --single-transaction --quick --no-autocommit ' .
                '--host=%s --user=%s --password=%s %s > %s',
                escapeshellarg($this->config->get('database.host')),
                escapeshellarg($this->config->get('database.user')),
                escapeshellarg($this->config->get('database.password')),
                escapeshellarg($this->config->get('database.name')),
                escapeshellarg($filename)
            );
            
            // Execute backup command
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                throw new \Exception('Backup failed');
            }

            // Compress backup
            $gzFilename = $filename . '.gz';
            $gz = gzopen($gzFilename, 'w9');
            gzwrite($gz, file_get_contents($filename));
            gzclose($gz);
            
            // Remove uncompressed file
            unlink($filename);

            $this->logAnalysis('backup', ['file' => $gzFilename]);
            return $gzFilename;

        } catch (\Exception $e) {
            $this->logError('backup', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string 
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Log analysis results
     */
    private function logAnalysis(string $type, array $data): void 
    {
        $logFile = $this->logPath . "/{$type}_" . date('Y-m-d') . '.log';
        $logData = date('Y-m-d H:i:s') . " - " . json_encode($data) . PHP_EOL;
        file_put_contents($logFile, $logData, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log error
     */
    private function logError(string $type, string $message): void 
    {
        $logFile = $this->logPath . "/errors_" . date('Y-m-d') . '.log';
        $logData = date('Y-m-d H:i:s') . " - {$type}: {$message}" . PHP_EOL;
        file_put_contents($logFile, $logData, FILE_APPEND | LOCK_EX);
    }
}
