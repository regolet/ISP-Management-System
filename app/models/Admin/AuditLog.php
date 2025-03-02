<?php
namespace App\Models\Admin;

use App\Core\Model;

class AuditLog extends Model 
{
    protected $table = 'activity_logs';
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at'
    ];

    /**
     * Log an action
     */
    public function logAction($userId, $action, $module, $recordId = null, $oldValues = null, $newValues = null) 
    {
        return $this->create([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get audit logs with filters
     */
    public function getLogs($filters = [], $page = 1, $limit = 50) 
    {
        $offset = ($page - 1) * $limit;
        $where = ['1 = 1'];
        $params = [];
        $types = '';

        if (!empty($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
        }

        if (!empty($filters['action'])) {
            $where[] = "action = ?";
            $params[] = $filters['action'];
            $types .= 's';
        }

        if (!empty($filters['module'])) {
            $where[] = "module = ?";
            $params[] = $filters['module'];
            $types .= 's';
        }

        if (!empty($filters['date_range'])) {
            switch ($filters['date_range']) {
                case 'today':
                    $where[] = "DATE(created_at) = CURRENT_DATE";
                    break;
                case 'week':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                    break;
                case 'month':
                    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
            }
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} 
                     WHERE {$whereClause}";
        
        $stmt = $this->db->prepare($countSql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];

        // Get logs with user details
        $sql = "SELECT l.*, u.username, u.role 
                FROM {$this->table} l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE {$whereClause}
                ORDER BY l.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'logs' => $logs,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get available modules
     */
    public function getModules() 
    {
        $sql = "SELECT DISTINCT module FROM {$this->table} ORDER BY module";
        $result = $this->db->query($sql);
        return array_column($result->fetch_all(MYSQLI_ASSOC), 'module');
    }

    /**
     * Get available actions
     */
    public function getActions() 
    {
        $sql = "SELECT DISTINCT action FROM {$this->table} ORDER BY action";
        $result = $this->db->query($sql);
        return array_column($result->fetch_all(MYSQLI_ASSOC), 'action');
    }

    /**
     * Clean old logs
     */
    public function cleanOldLogs($days = 90) 
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $days);
        return $stmt->execute();
    }
}
