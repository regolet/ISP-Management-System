<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Backup;
use App\Models\Admin\AuditLog;

class BackupController extends Controller 
{
    private $backupModel;
    private $auditLogModel;

    public function __construct() 
    {
        parent::__construct();
        $this->backupModel = new Backup();
        $this->auditLogModel = new AuditLog();
    }

    /**
     * Display backup list
     */
    public function index() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $page = $this->getQuery('page', 1);
        $result = $this->backupModel->getBackups($page);

        return $this->view('admin/backup/index', [
            'backups' => $result['backups'],
            'total_pages' => $result['pages'],
            'current_page' => $page,
            'layout' => 'navbar',
            'title' => 'Database Backups'
        ]);
    }

    /**
     * Create new backup
     */
    public function create() 
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $notes = $this->getPost('notes', '');
            $filename = $this->backupModel->createBackup($notes);

            // Log the action
            $this->auditLogModel->logAction(
                $_SESSION['user_id'],
                'create',
                'backup',
                null,
                null,
                ['filename' => $filename]
            );

            return $this->json([
                'success' => true,
                'message' => 'Backup created successfully'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to create backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download backup file
     */
    public function downloadBackup($id) 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        try {
            $backup = $this->backupModel->find($id);
            if (!$backup) {
                throw new \Exception('Backup not found');
            }

            // Log the action
            $this->auditLogModel->logAction(
                $_SESSION['user_id'],
                'download',
                'backup',
                $id,
                null,
                ['filename' => $backup['filename']]
            );

            // Use parent's download method
            $filePath = $this->backupModel->getBackupPath($backup['filename']);
            return parent::download($filePath, $backup['filename']);

        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return $this->redirect('/admin/backup');
        }
    }

    /**
     * Restore backup
     */
    public function restore($id) 
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $backup = $this->backupModel->find($id);
            if (!$backup) {
                throw new \Exception('Backup not found');
            }

            // Create a new backup before restoring
            $this->backupModel->createBackup('Auto-backup before restore');

            // Restore the selected backup
            $this->backupModel->restoreBackup($backup['filename']);

            // Log the action
            $this->auditLogModel->logAction(
                $_SESSION['user_id'],
                'restore',
                'backup',
                $id,
                null,
                ['filename' => $backup['filename']]
            );

            return $this->json([
                'success' => true,
                'message' => 'Backup restored successfully'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to restore backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete backup
     */
    public function delete($id) 
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $backup = $this->backupModel->find($id);
            if (!$backup) {
                throw new \Exception('Backup not found');
            }

            $this->backupModel->deleteBackup($id);

            // Log the action
            $this->auditLogModel->logAction(
                $_SESSION['user_id'],
                'delete',
                'backup',
                $id,
                ['filename' => $backup['filename']],
                null
            );

            return $this->json([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to delete backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update backup settings
     */
    public function updateSettings() 
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $maxBackups = (int)$this->getPost('max_backups', 10);
            $this->backupModel->setMaxBackups($maxBackups);

            // Log the action
            $this->auditLogModel->logAction(
                $_SESSION['user_id'],
                'update',
                'backup_settings',
                null,
                null,
                ['max_backups' => $maxBackups]
            );

            return $this->json([
                'success' => true,
                'message' => 'Backup settings updated successfully'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }
}
