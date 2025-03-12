<?php
namespace App\Models;

use PDO;

class Olt {
    // Database connection and table names
    private $conn;
    private $table_devices = "olt_devices";
    private $table_ports = "olt_ports";
    private $table_logs = "olt_logs";

    // Object properties
    public $id;
    public $name;
    public $model;
    public $ip_address;
    // removed username and password
    public $location;
    public $uptime;
    public $firmware_version;
    public $total_ports;
    public $used_ports;
    public $status;
    public $last_sync;
    public $notes;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all OLT devices with optional filtering
     */
    public function getAll($page = 1, $per_page = 10, $search = '', $status = '', $sort = 'id', $order = 'ASC') {
        // Calculate offset for pagination
        $offset = ($page - 1) * $per_page;
        
        // Build base query
        $query = "SELECT * FROM " . $this->table_devices . " WHERE 1=1";
        
        $params = [];
        
        // Add search condition if provided
        if (!empty($search)) {
            $query .= " AND (name LIKE ? OR model LIKE ? OR ip_address LIKE ? OR location LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        // Add status condition if provided
        if (!empty($status)) {
            $query .= " AND status = ?";
            $params[] = $status;
        }
        
        // Add sorting
        $allowed_sort_fields = ['id', 'name', 'model', 'ip_address', 'status', 'last_sync'];
        $sort = in_array($sort, $allowed_sort_fields) ? $sort : 'id';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY " . $sort . " " . $order;
        
        // Add pagination
        $query .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $per_page;
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query with params array
        if ($params) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total number of OLT devices with filters
     */
    public function getTotal($search = '', $status = '') {
        // Build base query
        $query = "SELECT COUNT(*) as total FROM " . $this->table_devices . " WHERE 1=1";
        
        $params = [];
        
        // Add search condition if provided
        if (!empty($search)) {
            $query .= " AND (name LIKE ? OR model LIKE ? OR ip_address LIKE ? OR location LIKE ?)";
            $searchParam = "%{$search}%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        // Add status condition if provided
        if (!empty($status)) {
            $query .= " AND status = ?";
            $params[] = $status;
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query with params array
        if ($params) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] ?? 0;
    }

    /**
     * Get OLT device by ID with ports
     */
    public function getById($id) {
        // Get OLT device details
        $query = "SELECT * FROM " . $this->table_devices . " WHERE id = ?";
                 
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        $olt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$olt) {
            return null;
        }
        
        // Get OLT ports
        try {
            // First check if client_subscription_id column exists
            $checkQuery = "PRAGMA table_info(" . $this->table_ports . ")";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $columns = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $hasClientSubscriptionId = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'client_subscription_id') {
                    $hasClientSubscriptionId = true;
                    break;
                }
            }
            
            if ($hasClientSubscriptionId) {
                $query = "SELECT p.*, cs.subscription_number, c.first_name, c.last_name, c.email, c.phone
                          FROM " . $this->table_ports . " p
                          LEFT JOIN client_subscriptions cs ON p.client_subscription_id = cs.id
                          LEFT JOIN clients c ON cs.client_id = c.id
                          WHERE p.olt_id = ?
                          ORDER BY CAST(p.port_number AS UNSIGNED) ASC";
            } else {
                // Fallback query without client_subscription_id joins
                $query = "SELECT p.*
                          FROM " . $this->table_ports . " p
                          WHERE p.olt_id = ?
                          ORDER BY CAST(p.port_number AS UNSIGNED) ASC";
            }
        } catch (PDOException $e) {
            // If PRAGMA fails (not SQLite), use a simpler query
            $query = "SELECT p.*
                      FROM " . $this->table_ports . " p
                      WHERE p.olt_id = ?
                      ORDER BY CAST(p.port_number AS UNSIGNED) ASC";
        }
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        $ports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get latest logs
        $query = "SELECT * FROM " . $this->table_logs . " 
                 WHERE olt_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 10";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'olt' => $olt,
            'ports' => $ports,
            'logs' => $logs
        ];
    }

    /**
     * Create new OLT device
     */
    public function create() {
        // Query to insert OLT
        $query = "INSERT INTO " . $this->table_devices . "
                (name, model, ip_address, location, total_ports, status, firmware_version, notes)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->model = htmlspecialchars(strip_tags($this->model));
        $this->ip_address = htmlspecialchars(strip_tags($this->ip_address));
        $this->location = $this->location ? htmlspecialchars(strip_tags($this->location)) : null;
        $this->firmware_version = $this->firmware_version ? htmlspecialchars(strip_tags($this->firmware_version)) : null;
        $this->notes = $this->notes ? htmlspecialchars(strip_tags($this->notes)) : null;
        
        // Status defaults to 'active' if not set
        $this->status = in_array($this->status, ['active', 'maintenance', 'offline']) ? $this->status : 'active';
        
        // Execute statement
        $result = $stmt->execute([
            $this->name,
            $this->model,
            $this->ip_address,
            $this->location,
            $this->total_ports,
            $this->status,
            $this->firmware_version,
            $this->notes
        ]);
        
        if ($result) {
            // Get the last inserted ID
            $this->id = $this->conn->lastInsertId();
            
            // Create default ports based on total_ports
            $this->createDefaultPorts();
            
            // Log OLT creation
            $this->logAction('info', 'OLT device created', ['device_id' => $this->id]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Update existing OLT device
     */
    public function update() {
        $query = "UPDATE " . $this->table_devices . "
                SET
                    name = ?,
                    model = ?,
                    ip_address = ?,
                    location = ?,
                    total_ports = ?,
                    status = ?,
                    firmware_version = ?,
                    notes = ?
                WHERE
                    id = ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->model = htmlspecialchars(strip_tags($this->model));
        $this->ip_address = htmlspecialchars(strip_tags($this->ip_address));
        $this->location = $this->location ? htmlspecialchars(strip_tags($this->location)) : null;
        $this->firmware_version = $this->firmware_version ? htmlspecialchars(strip_tags($this->firmware_version)) : null;
        $this->notes = $this->notes ? htmlspecialchars(strip_tags($this->notes)) : null;
        $this->status = in_array($this->status, ['active', 'maintenance', 'offline']) ? $this->status : 'active';
        
        // Execute statement
        $result = $stmt->execute([
            $this->name,
            $this->model,
            $this->ip_address,
            $this->location,
            $this->total_ports,
            $this->status,
            $this->firmware_version,
            $this->notes,
            $this->id
        ]);
        
        if ($result) {
            // Log OLT update
            $this->logAction('info', 'OLT device updated', ['device_id' => $this->id]);
            return true;
        }
        
        return false;
    }

    /**
     * Delete OLT device
     */
    public function delete($id) {
        // First check if OLT has active client connections
        $query = "SELECT COUNT(*) as count FROM " . $this->table_ports . " WHERE olt_id = ? AND client_subscription_id IS NOT NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            throw new Exception("Cannot delete OLT with active client connections");
        }
        
        // Delete the OLT
        $query = "DELETE FROM " . $this->table_devices . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    /**
     * Get OLT port by ID
     */
    public function getPortById($id) {
        try {
            // First check if client_subscription_id column exists
            $checkQuery = "PRAGMA table_info(" . $this->table_ports . ")";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $columns = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $hasClientSubscriptionId = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'client_subscription_id') {
                    $hasClientSubscriptionId = true;
                    break;
                }
            }
            
            if ($hasClientSubscriptionId) {
                $query = "SELECT p.*, o.name as olt_name, o.ip_address, cs.subscription_number, 
                                c.id as client_id, c.first_name, c.last_name, c.email, c.phone
                         FROM " . $this->table_ports . " p
                         JOIN " . $this->table_devices . " o ON p.olt_id = o.id
                         LEFT JOIN client_subscriptions cs ON p.client_subscription_id = cs.id
                         LEFT JOIN clients c ON cs.client_id = c.id
                         WHERE p.id = ?";
            } else {
                // Fallback query without client_subscription_id joins
                $query = "SELECT p.*, o.name as olt_name, o.ip_address
                         FROM " . $this->table_ports . " p
                         JOIN " . $this->table_devices . " o ON p.olt_id = o.id
                         WHERE p.id = ?";
            }
        } catch (PDOException $e) {
            // If PRAGMA fails (not SQLite), use a simpler query
            $query = "SELECT p.*, o.name as olt_name, o.ip_address
                     FROM " . $this->table_ports . " p
                     JOIN " . $this->table_devices . " o ON p.olt_id = o.id
                     WHERE p.id = ?";
        }
                 
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update OLT port
     */
    public function updatePort($id, $data) {
        // Get original port data for logging changes
        $originalPort = $this->getPortById($id);
        
        try {
            // First check if client_subscription_id column exists
            $checkQuery = "PRAGMA table_info(" . $this->table_ports . ")";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $columns = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $hasClientSubscriptionId = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'client_subscription_id') {
                    $hasClientSubscriptionId = true;
                    break;
                }
            }
            
            if ($hasClientSubscriptionId) {
                $query = "UPDATE " . $this->table_ports . "
                        SET
                            status = ?,
                            client_subscription_id = ?,
                            description = ?,
                            signal_strength = ?
                        WHERE
                            id = ?";
                            
                $stmt = $this->conn->prepare($query);
                
                // Sanitize inputs
                $status = in_array($data['status'], ['active', 'inactive', 'fault', 'reserved']) ? $data['status'] : 'inactive';
                $client_subscription_id = !empty($data['client_subscription_id']) ? $data['client_subscription_id'] : null;
                $description = !empty($data['description']) ? htmlspecialchars(strip_tags($data['description'])) : null;
                $signal_strength = !empty($data['signal_strength']) ? $data['signal_strength'] : null;
                
                $result = $stmt->execute([
                    $status,
                    $client_subscription_id,
                    $description,
                    $signal_strength,
                    $id
                ]);
            } else {
                // Fallback query without client_subscription_id
                $query = "UPDATE " . $this->table_ports . "
                        SET
                            status = ?,
                            description = ?,
                            signal_strength = ?
                        WHERE
                            id = ?";
                            
                $stmt = $this->conn->prepare($query);
                
                // Sanitize inputs
                $status = in_array($data['status'], ['active', 'inactive', 'fault', 'reserved']) ? $data['status'] : 'inactive';
                $description = !empty($data['description']) ? htmlspecialchars(strip_tags($data['description'])) : null;
                $signal_strength = !empty($data['signal_strength']) ? $data['signal_strength'] : null;
                
                $result = $stmt->execute([
                    $status,
                    $description,
                    $signal_strength,
                    $id
                ]);
            }
        } catch (PDOException $e) {
            // If PRAGMA fails (not SQLite), use a simpler query
            $query = "UPDATE " . $this->table_ports . "
                    SET
                        status = ?,
                        description = ?,
                        signal_strength = ?
                    WHERE
                        id = ?";
                        
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $status = in_array($data['status'], ['active', 'inactive', 'fault', 'reserved']) ? $data['status'] : 'inactive';
            $description = !empty($data['description']) ? htmlspecialchars(strip_tags($data['description'])) : null;
            $signal_strength = !empty($data['signal_strength']) ? $data['signal_strength'] : null;
            
            $result = $stmt->execute([
                $status,
                $description,
                $signal_strength,
                $id
            ]);
        }
        
        if ($result) {
            // Update used ports count
            $this->updateUsedPortsCount($originalPort['olt_id']);
            
            // Log port update
            $changes = [];
            if ($originalPort['status'] != $status) {
                $changes['status'] = ['from' => $originalPort['status'], 'to' => $status];
            }
            
            // Only check client_subscription_id if it exists in the original port data
            if (isset($originalPort['client_subscription_id']) && isset($client_subscription_id) && 
                $originalPort['client_subscription_id'] != $client_subscription_id) {
                $changes['client_subscription'] = ['from' => $originalPort['client_subscription_id'], 'to' => $client_subscription_id];
            }
            
            if (!empty($changes)) {
                $this->logAction('port_change', 'Port settings changed', [
                    'port_id' => $id,
                    'port_number' => $originalPort['port_number'],
                    'changes' => $changes
                ], $originalPort['olt_id']);
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Create default ports when OLT is created
     */
    private function createDefaultPorts() {
        if (!$this->id || !$this->total_ports) {
            return false;
        }
        
        for ($i = 1; $i <= $this->total_ports; $i++) {
            $query = "INSERT INTO " . $this->table_ports . "
                    (olt_id, port_number, port_type, status)
                    VALUES
                    (?, ?, ?, ?)";
                    
            $stmt = $this->conn->prepare($query);
            $portType = ($i <= 8) ? 'PON' : 'ETHERNET';
            $stmt->execute([
                $this->id,
                $i,
                $portType,
                'inactive'
            ]);
        }
        
        return true;
    }

    /**
     * Update used ports count for an OLT
     */
    private function updateUsedPortsCount($oltId) {
        try {
            // First check if client_subscription_id column exists
            $checkQuery = "PRAGMA table_info(" . $this->table_ports . ")";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $columns = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $hasClientSubscriptionId = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'client_subscription_id') {
                    $hasClientSubscriptionId = true;
                    break;
                }
            }
            
            if ($hasClientSubscriptionId) {
                $query = "SELECT COUNT(*) as used_count FROM " . $this->table_ports . "
                         WHERE olt_id = ? AND client_subscription_id IS NOT NULL";
            } else {
                // If column doesn't exist, count active ports instead
                $query = "SELECT COUNT(*) as used_count FROM " . $this->table_ports . "
                         WHERE olt_id = ? AND status = 'active'";
            }
        } catch (PDOException $e) {
            // If PRAGMA fails (not SQLite), use active status as fallback
            $query = "SELECT COUNT(*) as used_count FROM " . $this->table_ports . "
                     WHERE olt_id = ? AND status = 'active'";
        }
                 
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$oltId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $used_count = $result['used_count'] ?? 0;
        
        $query = "UPDATE " . $this->table_devices . "
                 SET used_ports = ?
                 WHERE id = ?";
                 
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$used_count, $oltId]);
    }

    /**
     * Log OLT action
     */
    private function logAction($type, $message, $details = [], $oltId = null) {
        $oltId = $oltId ?? $this->id;
        
        if (!$oltId) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_logs . "
                (olt_id, log_type, message, details)
                VALUES
                (?, ?, ?, ?)";
                
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $oltId,
            $type,
            $message,
            json_encode($details)
        ]);
    }

    /**
     * Check if username and password columns exist, if not throw an exception
     * This method is no longer needed but kept for reference
     */
    private function ensureColumnsExist() {
        // Method intentionally left empty as username/password fields are no longer required
        return true;
    }

    /**
     * Get OLT statistics
     */
    public function getStats() {
        $stats = [];
        
        // Total OLTs
        $query = "SELECT COUNT(*) as total,
                 SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                 SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
                 SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline
                 FROM " . $this->table_devices;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Port statistics
        // First check if client_subscription_id column exists
        try {
            $checkQuery = "PRAGMA table_info(" . $this->table_ports . ")";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $columns = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $hasClientSubscriptionId = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'client_subscription_id') {
                    $hasClientSubscriptionId = true;
                    break;
                }
            }
            
            if ($hasClientSubscriptionId) {
                $query = "SELECT 
                         COUNT(*) as total_ports,
                         SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_ports,
                         SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_ports,
                         SUM(CASE WHEN status = 'fault' THEN 1 ELSE 0 END) as fault_ports,
                         SUM(CASE WHEN client_subscription_id IS NOT NULL THEN 1 ELSE 0 END) as connected_ports
                         FROM " . $this->table_ports;
            } else {
                // Fallback query without client_subscription_id
                $query = "SELECT 
                         COUNT(*) as total_ports,
                         SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_ports,
                         SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_ports,
                         SUM(CASE WHEN status = 'fault' THEN 1 ELSE 0 END) as fault_ports,
                         0 as connected_ports
                         FROM " . $this->table_ports;
            }
        } catch (PDOException $e) {
            // If PRAGMA fails (not SQLite), use a simpler query
            $query = "SELECT 
                     COUNT(*) as total_ports,
                     SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_ports,
                     SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_ports,
                     SUM(CASE WHEN status = 'fault' THEN 1 ELSE 0 END) as fault_ports,
                     0 as connected_ports
                     FROM " . $this->table_ports;
        }
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $portStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Merge port stats into main stats
        $stats = array_merge($stats, $portStats);
        
        return $stats;
    }

    /**
     * Sync with OLT device (This is a placeholder - would need actual integration code)
     */
    public function syncWithDevice($id) {
        // In a real implementation, this would connect to the OLT via SNMP, telnet, etc.
        // and update database with real-time data
        
        // For demo purposes, let's simulate a sync
        $query = "UPDATE " . $this->table_devices . "
                 SET last_sync = NOW(),
                     uptime = ?
                 WHERE id = ?";
                 
        $stmt = $this->conn->prepare($query);
        $uptime = rand(1, 365) . " days, " . rand(0, 23) . ":" . rand(0, 59) . ":" . rand(0, 59);
        
        $result = $stmt->execute([$uptime, $id]);
        
        if ($result) {
            // Log the sync
            $this->logAction('info', 'OLT device synced', [], $id);
            return true;
        }
        
        return false;
    }

    /**
     * Get diagnostic data for an OLT (This is a placeholder)
     */
    public function getDiagnostics($id) {
        // In a real implementation, this would fetch real diagnostic data
        $diagnostics = [
            'cpu_usage' => rand(5, 95) . '%',
            'memory_usage' => rand(20, 80) . '%',
            'temperature' => rand(30, 60) . '°C',
            'interfaces' => [],
        ];
        
        // Simulate interface stats
        for ($i = 1; $i <= 4; $i++) {
            $diagnostics['interfaces'][] = [
                'name' => 'ge-0/0/' . $i,
                'status' => (rand(0, 10) > 1) ? 'up' : 'down',
                'rx_packets' => rand(100000, 9999999),
                'tx_packets' => rand(100000, 9999999),
                'errors' => rand(0, 100)
            ];
        }
        
        return $diagnostics;
    }

    /**
     * Get OLT port utilization data
     */
    public function getPortUtilization($id) {
        $query = "SELECT status, COUNT(*) as count 
                 FROM " . $this->table_ports . " 
                 WHERE olt_id = ?
                 GROUP BY status";
                 
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['status']] = $row['count'];
        }
        
        return $result;
    }
}
