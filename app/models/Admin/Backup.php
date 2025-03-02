<?php
namespace App\Models\Admin;

use App\Core\Model;

class Backup extends Model 
{
    protected $table = 'backups';
    protected $primaryKey = 'id';
    protected $fillable = [
        'filename',
        'size',
        'created_by',
        'created_at',
        'notes'
    ];

    private $backupPath = 'storage/backups/';
    private $maxBackups = 10; // Maximum number of backups to keep

    /**
     * Create database backup
     */
    public function createBackup($notes = '') 
    {
        // Ensure backup directory exists
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        // Generate backup filename
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $this->backupPath . $filename;

        try {
            // Get database configuration
            $config = require_once APP_ROOT . '/config/database.php';
            
            // Build mysqldump command
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s %s > %s',
                escapeshellarg($config['host']),
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['database']),
                escapeshellarg($filepath)
            );

            // Execute backup command
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception('Database backup failed');
            }

            // Record backup in database
            $this->create([
                'filename' => $filename,
                'size' => filesize($filepath),
                'created_by' => $_SESSION['user_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]);

            // Clean old backups
            $this->cleanOldBackups();

            return $filename;

        } catch (\Exception $e) {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            throw $e;
        }
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup($filename) 
    {
        $filepath = $this->backupPath . $filename;

        if (!file_exists($filepath)) {
            throw new \Exception('Backup file not found');
        }

        try {
            // Get database configuration
            $config = require_once APP_ROOT . '/config/database.php';
            
            // Build mysql restore command
            $command = sprintf(
                'mysql --host=%s --user=%s --password=%s %s < %s',
                escapeshellarg($config['host']),
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['database']),
                escapeshellarg($filepath)
            );

            // Execute restore command
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception('Database restore failed');
            }

            return true;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Download backup file
     */
    public function downloadBackup($filename) 
    {
        $filepath = $this->backupPath . $filename;

        if (!file_exists($filepath)) {
            throw new \Exception('Backup file not found');
        }

        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));

        // Output file
        readfile($filepath);
        exit;
    }

    /**
     * Delete backup
     */
    public function deleteBackup($id) 
    {
        $backup = $this->find($id);
        if (!$backup) {
            throw new \Exception('Backup not found');
        }

        $filepath = $this->backupPath . $backup['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        return $this->delete($id);
    }

    /**
     * Clean old backups
     */
    private function cleanOldBackups() 
    {
        // Get all backups ordered by creation date
        $backups = $this->db->query(
            "SELECT * FROM {$this->table} ORDER BY created_at DESC"
        )->fetch_all(MYSQLI_ASSOC);

        // Keep only the most recent backups
        foreach (array_slice($backups, $this->maxBackups) as $backup) {
            $this->deleteBackup($backup['id']);
        }
    }

    /**
     * Get backup list with pagination
     */
    public function getBackups($page = 1, $limit = 10) 
    {
        $offset = ($page - 1) * $limit;

        // Get total count
        $total = $this->db->query(
            "SELECT COUNT(*) as total FROM {$this->table}"
        )->fetch_assoc()['total'];

        // Get backups with user details
        $sql = "SELECT b.*, u.username 
                FROM {$this->table} b
                LEFT JOIN users u ON b.created_by = u.id
                ORDER BY b.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $backups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'backups' => $backups,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get backup file path
     */
    public function getBackupPath($filename) 
    {
        return $this->backupPath . $filename;
    }

    /**
     * Set maximum number of backups to keep
     */
    public function setMaxBackups($count) 
    {
        $this->maxBackups = max(1, (int)$count);
    }
}
