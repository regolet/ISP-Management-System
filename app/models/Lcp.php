<?php
namespace App\Models;

use PDO;

class Lcp {
    private $db;

    // Properties
    public $id;
    public $name;
    public $model;
    public $location;
    public $latitude;
    public $longitude;
    public $total_ports;
    public $status;
    public $parent_olt_id;
    public $parent_port_id;
    public $installation_date;
    public $notes;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get all LCP devices with optional filtering and pagination
     */
    public function getAll($page = 1, $per_page = 10, $search = '', $status = '', $sort = 'id', $order = 'ASC') {
        try {
            // Calculate starting index for pagination
            $start = ($page - 1) * $per_page;

            // Base query
            $query = "SELECT l.*, IFNULL(o.name, 'None') as parent_olt_name 
                      FROM lcp_devices l
                      LEFT JOIN olt_devices o ON l.parent_olt_id = o.id
                      WHERE 1=1";

            // Add search filter if provided
            if (!empty($search)) {
                $query .= " AND (l.name LIKE :search OR l.model LIKE :search OR l.location LIKE :search)";
            } else {
                // When search is empty, add a dummy condition that's always true
                $query .= " AND (1=1)";
            }

            // Add status filter if provided
            if (!empty($status)) {
                $query .= " AND l.status = :status";
            }

            // Add sorting
            $query .= " ORDER BY l." . $sort . " " . $order;

            // Add pagination - using LIMIT directly since parameters are safely sanitized
            $query .= " LIMIT " . (int)$start . ", " . (int)$per_page;

            // Prepare and bind
            $stmt = $this->db->prepare($query);

            // Bind search parameter if provided
            if (!empty($search)) {
                $searchTerm = "%" . $search . "%";
                $stmt->bindValue(':search', $searchTerm);
            }

            // Bind status parameter if provided
            if (!empty($status)) {
                $stmt->bindValue(':status', $status);
            }

            // Execute and fetch
            $stmt->execute();
            error_log("LCP getAll query executed: " . $query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LCP::getAll() - Error getting LCP devices: " . $e->getMessage());
            error_log("Query attempted: " . $query);
            return [];
        }
    }

    /**
     * Get total number of LCP devices
     */
    public function getTotal($search = '', $status = '') {
        try {
            $query = "SELECT COUNT(*) FROM lcp_devices WHERE 1=1";

            // Add search filter if provided
            if (!empty($search)) {
                $query .= " AND (name LIKE :search OR model LIKE :search OR location LIKE :search)";
            }

            // Add status filter if provided
            if (!empty($status)) {
                $query .= " AND status = :status";
            }

            $stmt = $this->db->prepare($query);

            // Bind search parameter if provided
            if (!empty($search)) {
                $searchTerm = "%" . $search . "%";
                $stmt->bindValue(':search', $searchTerm);
            }

            // Bind status parameter if provided
            if (!empty($status)) {
                $stmt->bindValue(':status', $status);
            }

            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("LCP::getTotal() - Error getting LCP count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get LCP device by ID
     */
    public function getById($id) {
        $query = "SELECT l.*, op.port_number AS parent_port_number
                  FROM lcp_devices l
                  LEFT JOIN olt_ports op ON l.parent_port_id = op.id
                  WHERE l.id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new LCP device
     */
    public function create() {
        $query = "INSERT INTO lcp_devices (name, model, location, latitude, longitude, total_ports, status, parent_olt_id, parent_port_id, installation_date, notes) 
                  VALUES (:name, :model, :location, :latitude, :longitude, :total_ports, :status, :parent_olt_id, :parent_port_id, :installation_date, :notes)";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':name', $this->name);
        $stmt->bindValue(':model', $this->model);
        $stmt->bindValue(':location', $this->location);
        $stmt->bindValue(':latitude', $this->latitude);
        $stmt->bindValue(':longitude', $this->longitude);
        $stmt->bindValue(':total_ports', $this->total_ports, PDO::PARAM_INT);
        $stmt->bindValue(':status', $this->status);
        $stmt->bindValue(':parent_olt_id', $this->parent_olt_id, PDO::PARAM_INT);
        $stmt->bindValue(':parent_port_id', $this->parent_port_id, PDO::PARAM_INT);
        $stmt->bindValue(':installation_date', $this->installation_date);
        $stmt->bindValue(':notes', $this->notes);

        try {
            $stmt->execute();
            $this->id = $this->db->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log("LCP::create() - Error creating LCP device: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update existing LCP device
     */
    public function update() {
        $query = "UPDATE lcp_devices 
                  SET name = :name, model = :model, location = :location, latitude = :latitude, longitude = :longitude, 
                      total_ports = :total_ports, status = :status, parent_olt_id = :parent_olt_id, parent_port_id = :parent_port_id, 
                      installation_date = :installation_date, notes = :notes
                  WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $this->name);
        $stmt->bindValue(':model', $this->model);
        $stmt->bindValue(':location', $this->location);
        $stmt->bindValue(':latitude', $this->latitude);
        $stmt->bindValue(':longitude', $this->longitude);
        $stmt->bindValue(':total_ports', $this->total_ports, PDO::PARAM_INT);
        $stmt->bindValue(':status', $this->status);
        $stmt->bindValue(':parent_olt_id', $this->parent_olt_id, PDO::PARAM_INT);
        $stmt->bindValue(':parent_port_id', $this->parent_port_id, PDO::PARAM_INT);
        $stmt->bindValue(':installation_date', $this->installation_date);
        $stmt->bindValue(':notes', $this->notes);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("LCP::update() - Error updating LCP device: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete LCP device
     */
    public function delete($id) {
        $query = "DELETE FROM lcp_devices WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("LCP::delete() - Error deleting LCP device: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get port by ID
     */
    public function getPortById($id) {
        $query = "SELECT * FROM lcp_ports WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update port
     */
    public function updatePort($id, $data) {
        $query = "UPDATE lcp_ports SET status = :status, signal_strength = :signal_strength, client_subscription_id = :client_subscription_id, description = :description WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':signal_strength', $data['signal_strength']);
        $stmt->bindValue(':client_subscription_id', $data['client_subscription_id']);
        $stmt->bindValue(':description', $data['description']);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("LCP::updatePort() - Error updating port: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get available OLT ports for LCP connection
     */
    public function getAvailableOltPorts($oltId = null) {
        if ($oltId === null) {
            return [];
        }

        $query = "SELECT 
            op.id AS port_id,
            op.port_number,
            op.capacity,
            op.status AS port_status,
            o.id AS olt_id,
            o.name AS olt_name
        FROM olt_ports op
        JOIN olt_devices o ON op.olt_id = o.id
        LEFT JOIN lcp_devices lcp ON op.id = lcp.parent_port_id AND op.olt_id = lcp.parent_olt_id
        WHERE op.olt_id = :oltId
        AND op.status = 'inactive'
        GROUP BY op.id, op.port_number, op.capacity, op.status, o.id, o.name
        HAVING COUNT(lcp.id) < op.capacity";


        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':oltId', $oltId, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LCP::getAvailableOltPorts - SQL error: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Add maintenance record
     */
    public function addMaintenance($data) {
        $query = "INSERT INTO lcp_maintenance (lcp_id, maintenance_type, status, start_date, end_date, description, notes) 
                  VALUES (:lcp_id, :maintenance_type, :status, :start_date, :end_date, :description, :notes)";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':lcp_id', $data['lcp_id']);
        $stmt->bindValue(':maintenance_type', $data['maintenance_type']);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':start_date', $data['start_date']);
        $stmt->bindValue(':end_date', $data['end_date']);
        $stmt->bindValue(':description', $data['description']);
        $stmt->bindValue(':notes', $data['notes']);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("LCP::addMaintenance() - Error adding maintenance record: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update maintenance record
     */
    public function updateMaintenance($id, $data) {
        $query = "UPDATE lcp_maintenance 
                  SET maintenance_type = :maintenance_type, status = :status, start_date = :start_date, 
                      end_date = :end_date, description = :description, notes = :notes
                  WHERE id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':maintenance_type', $data['maintenance_type']);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':start_date', $data['start_date']);
        $stmt->bindValue(':end_date', $data['end_date']);
        $stmt->bindValue(':description', $data['description']);
        $stmt->bindValue(':notes', $data['notes']);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("LCP::updateMaintenance() - Error updating maintenance record: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete maintenance record
     */
    public function deleteMaintenance($id) {
        $query = "DELETE FROM lcp_maintenance WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("LCP::deleteMaintenance() - Error deleting maintenance record: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get LCP stats
     */
    public function getStats() {
        $query = "SELECT 
                      (SELECT COUNT(*) FROM lcp_devices) as total_lcps,
                      (SELECT COUNT(*) FROM lcp_devices WHERE status = 'active') as active_lcps,
                      (SELECT COUNT(*) FROM lcp_devices WHERE status = 'maintenance') as maintenance_lcps,
                      (SELECT COUNT(*) FROM lcp_devices WHERE status = 'offline') as offline_lcps";

        $stmt = $this->db->prepare($query);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LCP::getStats() - Error getting LCP stats: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get LCP dashboard data
     */
    public function getDashboardData() {
        $query = "SELECT 
                      (SELECT COUNT(*) FROM lcp_devices) as total_lcps,
                      (SELECT COUNT(*) FROM lcp_devices WHERE status = 'active') as active_lcps,
                      (SELECT COUNT(*) FROM lcp_devices WHERE status = 'maintenance') as maintenance_lcps,
                      (SELECT COUNT(*) FROM lcp_devices WHERE status = 'offline') as offline_lcps
                  FROM dual";

        $stmt = $this->db->prepare($query);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LCP::getDashboardData() - Error getting LCP dashboard data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Export LCP data to CSV
     */
    public function exportToCsv() {
        $query = "SELECT * FROM lcp_devices";
        $stmt = $this->db->prepare($query);

        try {
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($data)) {
                return false;
            }

            // CSV headers
            $csv = "ID,Name,Model,Location,Latitude,Longitude,Total Ports,Status,Parent OLT ID,Parent Port ID,Installation Date,Notes\n";

            // CSV data
            foreach ($data as $row) {
                $csv .= $row['id'] . "," .
                        str_replace(',', '', $row['name']) . "," .
                        str_replace(',', '', $row['model']) . "," .
                        str_replace(',', '', $row['location']) . "," .
                        $row['latitude'] . "," .
                        $row['longitude'] . "," .
                        $row['total_ports'] . "," .
                        $row['status'] . "," .
                        $row['parent_olt_id'] . "," .
                        $row['parent_port_id'] . "," .
                        $row['installation_date'] . "," .
                        str_replace(',', '', $row['notes']) . "\n";
            }

            return $csv;
        } catch (PDOException $e) {
            error_log("LCP::exportToCsv() - Error exporting LCP data to CSV: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get LCP port utilization data
     */
    public function getPortUtilization($id) {
        $query = "SELECT 
                      (SELECT COUNT(*) FROM lcp_ports WHERE lcp_device_id = :id AND status = 'active') as active_ports,
                      (SELECT COUNT(*) FROM lcp_ports WHERE lcp_device_id = :id AND status = 'inactive') as inactive_ports,
                      (SELECT COUNT(*) FROM lcp_ports WHERE lcp_device_id = :id AND status = 'fault') as fault_ports,
                      (SELECT COUNT(*) FROM lcp_ports WHERE lcp_device_id = :id AND status = 'reserved') as reserved_ports";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LCP::getPortUtilization() - Error getting LCP port utilization data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get LCPs with fault ports for maintenance
     */
    public function getLcpsWithFaultPorts() {
        $query = "SELECT l.* 
                  FROM lcp_devices l
                  INNER JOIN lcp_ports p ON l.id = p.lcp_device_id
                  WHERE p.status = 'fault'";

        $stmt = $this->db->prepare($query);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LCP::getLcpsWithFaultPorts() - Error getting LCPs with fault ports: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get connected clients to LCP
     */
    public function getConnectedClients($id) {
        $query = "SELECT 
                      c.*,
                      s.name as subscription
                  FROM clients c
                  INNER JOIN client_subscriptions s ON c.subscription_id = s.id
                  WHERE c.lcp_device_id = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LCP::getConnectedClients() - Error getting connected clients: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate LCP health report
     */
    public function generateHealthReport($id) {
        $query = "SELECT * FROM lcp_devices WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id);

        try {
            $stmt->execute();
            $lcp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$lcp) {
                return false;
            }

            // Basic health report data
            $report = [
                'lcp_name' => $lcp['name'],
                'lcp_model' => $lcp['model'],
                'lcp_location' => $lcp['location'],
                'status' => $lcp['status'],
                'total_ports' => $lcp['total_ports']
            ];

            // Get port utilization
            $portUtilization = $this->getPortUtilization($id);
            if ($portUtilization) {
                $report['active_ports'] = $portUtilization['active_ports'];
                $report['inactive_ports'] = $portUtilization['inactive_ports'];
                $report['fault_ports'] = $portUtilization['fault_ports'];
                $report['reserved_ports'] = $portUtilization['reserved_ports'];
            }

            // Get connected clients
            $connectedClients = $this->getConnectedClients($id);
            $report['connected_clients'] = $connectedClients;

            return $report;
        } catch (PDOException $e) {
            error_log("LCP::generateHealthReport() - Error generating LCP health report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get upcoming maintenance tasks
     */
    public function getUpcomingMaintenance($days = 7, $limit = 10) {
        $query = "SELECT * FROM lcp_maintenance 
                  WHERE start_date >= CURDATE() AND start_date <= CURDATE() + INTERVAL :days DAY
                  ORDER BY start_date ASC
                  LIMIT :limit";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LCP::getUpcomingMaintenance() - Error getting upcoming maintenance tasks: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get maintenance history for an LCP
     */
    public function getMaintenanceHistory($id, $limit = 10) {
        $query = "SELECT * FROM lcp_maintenance WHERE lcp_id = :id ORDER BY start_date DESC LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LCP::getMaintenanceHistory() - Error getting maintenance history: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Batch update LCP status
     */
    public function batchUpdateStatus($ids, $status) {
        $query = "UPDATE lcp_devices SET status = :status WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':status', $status);

        // Bind each ID
        foreach ($ids as $key => $id) {
            $stmt->bindValue($key + 1, $id, PDO::PARAM_INT);
        }

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("LCP::batchUpdateStatus() - Error batch updating LCP status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get spatial data for all LCPs (for map view)
     */
    public function getSpatialData() {
        $query = "SELECT id, name, latitude, longitude FROM lcp_devices WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
        $stmt = $this->db->prepare($query);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LCP::getSpatialData() - Error getting spatial data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find nearest LCPs to coordinates
     */
    public function findNearest($latitude, $longitude, $limit = 5) {
        $query = "SELECT id, name, latitude, longitude,
                         (6371 * acos(cos(radians(:latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians(:longitude)) + sin(radians(:latitude)) * sin(radians(latitude)))) AS distance
                  FROM lcp_devices
                  WHERE latitude IS NOT NULL AND longitude IS NOT NULL
                  ORDER BY distance
                  LIMIT :limit";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':latitude', $latitude);
        $stmt->bindValue(':longitude', $longitude);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("LCP::findNearest() - Error finding nearest LCPs: " . $e->getMessage());
            return false;
        }
    }
}
?>