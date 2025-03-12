<?php
namespace App\Controllers;

class DashboardController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getDashboardData() {
        try {
            return [
                'summary' => [
                    'customerCount' => $this->getCustomerCount(),
                    'pendingBillsCount' => $this->getPendingBillsCount(),
                    'activeTicketsCount' => $this->getActiveTicketsCount()
                ],
                'recentActivities' => $this->getRecentActivities(),
                'billingOverview' => $this->getBillingOverview(),
                'ticketOverview' => $this->getTicketOverview(),
                'networkStatus' => $this->getNetworkStatus()
            ];
        } catch(\PDOException $e) {
            error_log("Error fetching dashboard data: " . $e->getMessage());
            return [
                'summary' => [
                    'customerCount' => 0,
                    'pendingBillsCount' => 0,
                    'activeTicketsCount' => 0
                ],
                'recentActivities' => [],
                'billingOverview' => [],
                'ticketOverview' => [],
                'networkStatus' => []
            ];
        }
    }

    private function getCustomerCount() {
        return $this->db->query("SELECT COUNT(*) FROM clients WHERE status = 'active'")->fetchColumn();
    }



    private function getPendingBillsCount() {
        return $this->db->query("SELECT COUNT(*) FROM billing WHERE status = 'pending'")->fetchColumn();
    }

    private function getActiveTicketsCount() {
        return $this->db->query("SELECT COUNT(*) FROM support_tickets WHERE status != 'closed'")->fetchColumn();
    }

    private function getRecentActivities($limit = 5) {
        $query = "SELECT 
                    al.created_at,
                    al.activity_type,
                    al.description,
                    CONCAT(c.first_name, ' ', c.last_name) as client_name,
                    u.username as user_name
                FROM activity_logs al
                LEFT JOIN clients c ON al.client_id = c.id
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getBillingOverview() {
        $query = "SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(total_amount) as total
                FROM billing
                GROUP BY status";
        
        return $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getTicketOverview() {
        $query = "SELECT 
                    status,
                    priority,
                    COUNT(*) as count
                FROM support_tickets
                GROUP BY status, priority
                ORDER BY 
                    FIELD(priority, 'high', 'medium', 'low'),
                    FIELD(status, 'open', 'in_progress', 'resolved', 'closed')";
        
        return $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getNetworkStatus() {
        $query = "SELECT 
                    type,
                    status,
                    COUNT(*) as count
                FROM network_equipment
                GROUP BY type, status
                ORDER BY 
                    FIELD(status, 'active', 'maintenance', 'inactive'),
                    type";
        
        return $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function logActivity($userId, $clientId, $type, $description, $ipAddress = null) {
        try {
            $query = "INSERT INTO activity_logs 
                    (user_id, client_id, activity_type, description, ip_address) 
                    VALUES 
                    (:user_id, :client_id, :type, :description, :ip_address)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $userId,
                ':client_id' => $clientId,
                ':type' => $type,
                ':description' => $description,
                ':ip_address' => $ipAddress ?? $_SERVER['REMOTE_ADDR']
            ]);
            return true;
        } catch(\PDOException $e) {
            error_log("Error logging activity: " . $e->getMessage());
            return false;
        }
    }
}
