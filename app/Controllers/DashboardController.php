
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
     * Get dashboard data for display
     * 
     * @return array Dashboard data
     */
    public function getDashboardData()
    {
        // Sample dashboard data - you would normally fetch this from the database
        return [
            'statistics' => [
                'clients' => $this->getClientCount(),
                'active_subscriptions' => $this->getActiveSubscriptionCount(),
                'total_revenue' => $this->getTotalRevenue(),
                'pending_invoices' => $this->getPendingInvoiceCount()
            ],
            'recent_activities' => $this->getRecentActivities(5),
            'upcoming_payments' => $this->getUpcomingPayments(5)
        ];
    }

    /**
     * Get client count
     * 
     * @return int Client count
     */
    private function getClientCount()
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM clients");
            $stmt->execute();
            return $stmt->fetchColumn() ?: 0;
        } catch (\PDOException $e) {
            // Return 0 if table doesn't exist or query fails
            return 0;
        }
    }

    /**
     * Get active subscription count
     * 
     * @return int Active subscription count
     */
    private function getActiveSubscriptionCount()
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'");
            $stmt->execute();
            return $stmt->fetchColumn() ?: 0;
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Get total revenue
     * 
     * @return float Total revenue
     */
    private function getTotalRevenue()
    {
        try {
            $stmt = $this->db->prepare("SELECT SUM(amount) FROM payments WHERE status = 'completed'");
            $stmt->execute();
            return $stmt->fetchColumn() ?: 0;
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Get pending invoice count
     * 
     * @return int Pending invoice count
     */
    private function getPendingInvoiceCount()
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM invoices WHERE status = 'pending'");
            $stmt->execute();
            return $stmt->fetchColumn() ?: 0;
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Get recent activities
     * 
     * @param int $limit Number of activities to get
     * @return array Recent activities
     */
    private function getRecentActivities($limit = 5)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT :limit");
            $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Get upcoming payments
     * 
     * @param int $limit Number of payments to get
     * @return array Upcoming payments
     */
    private function getUpcomingPayments($limit = 5)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT i.*, c.name as client_name 
                FROM invoices i
                JOIN clients c ON i.client_id = c.id
                WHERE i.status = 'pending' AND i.due_date >= CURRENT_DATE
                ORDER BY i.due_date ASC
                LIMIT :limit
            ");
            $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Log activity
     * 
     * @param int $user_id User ID
     * @param int|null $target_id Target ID (optional)
     * @param string $action Action performed
     * @param string $description Description
     * @param string $ip_address IP address
     * @return bool Whether logging was successful
     */
    public function logActivity($user_id, $target_id, $action, $description, $ip_address)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, target_id, action, description, ip_address)
                VALUES (:user_id, :target_id, :action, :description, :ip_address)
            ");
            
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':target_id', $target_id);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':ip_address', $ip_address);
            
            return $stmt->execute();
        } catch (\PDOException $e) {
            // Create table if it doesn't exist
            $this->createActivityLogTable();
            
            // Try again
            try {
                return $this->logActivity($user_id, $target_id, $action, $description, $ip_address);
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * Create activity log table if it doesn't exist
     * 
     * @return bool Whether table creation was successful
     */
    private function createActivityLogTable()
    {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS activity_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    target_id INTEGER NULL,
                    action VARCHAR(50) NOT NULL,
                    description TEXT NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }
}
