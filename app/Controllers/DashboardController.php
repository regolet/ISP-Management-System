
<?php

namespace App\Controllers;

class DashboardController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get dashboard data
     * 
     * @return array Dashboard data
     */
    public function getDashboardData()
    {
        // Initialize data array
        $data = [
            'stats' => $this->getStats(),
            'recent_activities' => $this->getRecentActivities(),
            'recent_clients' => $this->getRecentClients(),
            'recent_invoices' => $this->getRecentInvoices()
        ];

        return $data;
    }

    /**
     * Get stats for dashboard
     * 
     * @return array Stats
     */
    private function getStats()
    {
        // Example stats - in a real application, fetch from the database
        return [
            'total_clients' => $this->countClients(),
            'total_invoices' => $this->countInvoices(),
            'pending_invoices' => $this->countPendingInvoices(),
            'revenue_this_month' => $this->calculateMonthlyRevenue()
        ];
    }

    /**
     * Count clients
     * 
     * @return int Count
     */
    private function countClients()
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM clients");
            $stmt->execute();
            return $stmt->fetchColumn() ?? 0;
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Count invoices
     * 
     * @return int Count
     */
    private function countInvoices()
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM invoices");
            $stmt->execute();
            return $stmt->fetchColumn() ?? 0;
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Count pending invoices
     * 
     * @return int Count
     */
    private function countPendingInvoices()
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM invoices WHERE status = 'pending'");
            $stmt->execute();
            return $stmt->fetchColumn() ?? 0;
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Calculate monthly revenue
     * 
     * @return float Revenue
     */
    private function calculateMonthlyRevenue()
    {
        try {
            $startDate = date('Y-m-01'); // First day of current month
            $endDate = date('Y-m-t'); // Last day of current month
            
            $stmt = $this->db->prepare("SELECT SUM(amount) FROM invoices WHERE status = 'paid' AND payment_date BETWEEN :start_date AND :end_date");
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            return $stmt->fetchColumn() ?? 0;
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Get recent activities
     * 
     * @return array Activities
     */
    private function getRecentActivities()
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM activities ORDER BY created_at DESC LIMIT 10");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get recent clients
     * 
     * @return array Clients
     */
    private function getRecentClients()
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM clients ORDER BY created_at DESC LIMIT 5");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get recent invoices
     * 
     * @return array Invoices
     */
    private function getRecentInvoices()
    {
        try {
            $stmt = $this->db->prepare("SELECT i.*, c.name as client_name FROM invoices i LEFT JOIN clients c ON i.client_id = c.id ORDER BY i.created_at DESC LIMIT 5");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Log activity
     * 
     * @param int $userId User ID
     * @param int|null $targetId Target ID (optional)
     * @param string $type Activity type
     * @param string $description Activity description
     * @param string $ipAddress IP address
     * @return bool Whether logging was successful
     */
    public function logActivity($userId, $targetId, $type, $description, $ipAddress)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO activities (user_id, target_id, type, description, ip_address, created_at) VALUES (:user_id, :target_id, :type, :description, :ip_address, :created_at)");
            
            $currentTime = date('Y-m-d H:i:s');
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':target_id', $targetId);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindParam(':created_at', $currentTime);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            return false;
        }
    }
}
