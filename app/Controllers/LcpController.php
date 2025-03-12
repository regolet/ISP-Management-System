<?php
namespace App\Controllers;

require_once dirname(__DIR__) . '/Models/Lcp.php';
 
class LcpController {
    private $db;
    private $lcp;
 
    public function __construct($db) {
        $this->db = $db;
        $this->lcp = new \App\Models\Lcp($db);
    }
 
    /**
     * Get all LCP devices with optional filtering and pagination
     */
    public function getLcpDevices($params = []) {
        // Ensure parameters are properly typed
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $per_page = isset($params['per_page']) ? (int)$params['per_page'] : 10;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? '';
        $sort = $params['sort'] ?? 'id';
        $order = strtoupper($params['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
 
        // Normalize sort field to ensure it's valid
        $allowed_sort_fields = ['id', 'name', 'model', 'location', 'status', 'total_ports', 'used_ports'];
        if (!in_array(str_replace('l.', '', $sort), $allowed_sort_fields)) {
            $sort = 'id'; // Default to id if invalid sort field
        }
 
        try {
            $lcpDevices = $this->lcp->getAll($page, $per_page, $search, $status, $sort, $order);
            $total = $this->lcp->getTotal($search, $status);
            
            return [
                'data' => $lcpDevices,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page)
            ];
        } catch (\Exception $e) {
            error_log("Error in getLcpDevices: " . $e->getMessage());
            // Return a graceful error response
            return [
                'data' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
 
    /**
     * Get LCP device by ID with ports, logs and maintenance history
     */
    public function getLcpById($id) {
        try {
            if (!is_numeric($id)) {
                return [
                    'success' => false,
                    'message' => 'Invalid LCP ID provided'
                ];
            }
            
            $lcpData = $this->lcp->getById($id);
            
            if (!$lcpData) {
                return [
                    'success' => false,
                    'message' => 'LCP device not found'
                ];
            }
            
            // Clean data to ensure it's serializable
            array_walk_recursive($lcpData, function(&$value) {
                if (is_string($value)) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            });
            
            return [
                'success' => true,
                'lcp' => $lcpData
            ];
        } catch (\Exception $e) {
            error_log("LcpController::getLcpById() error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error retrieving LCP details: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Create new LCP device
     */
    public function createLcp($data) {
        try {
            // Validate required fields
            if (empty($data['name'])) {
                return [
                    'success' => false,
                    'message' => 'Name is required'
                ];
            }
            
            if (empty($data['model'])) {
                return [
                    'success' => false,
                    'message' => 'Model is required'
                ];
            }
            
            if (empty($data['location'])) {
                return [
                    'success' => false,
                    'message' => 'Location is required'
                ];
            }
            
            if (empty($data['total_ports']) || !is_numeric($data['total_ports'])) {
                return [
                    'success' => false,
                    'message' => 'Valid total ports number is required'
                ];
            }
            
            // Validate parent port belongs to parent OLT if both are provided
            if (!empty($data['parent_olt_id']) && !empty($data['parent_port_id'])) {
                // Prepare to check if port belongs to the OLT
                // This validation is already in the model, so we'll let it handle it
            }
 
            // Set LCP properties
            $this->lcp->name = $data['name'];
            $this->lcp->model = $data['model'];
            $this->lcp->location = $data['location'];
            $this->lcp->latitude = $data['latitude'];
            $this->lcp->longitude = $data['longitude'];
            $this->lcp->total_ports = $data['total_ports'];
            $this->lcp->status = $data['status'];
            $this->lcp->parent_olt_id = $data['parent_olt_id'];
            $this->lcp->parent_port_id = $data['parent_port_id'];
            $this->lcp->installation_date = $data['installation_date'];
            $this->lcp->notes = $data['notes'];
 
            // Create LCP device
            if ($this->lcp->create()) {
                return [
                    'success' => true,
                    'message' => 'LCP device created successfully',
                    'lcp_id' => $this->lcp->id
                ];
            }
 
            return [
                'success' => false,
                'message' => 'Failed to create LCP device'
            ];
 
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Update existing LCP device
     */
    public function updateLcp($id, $data) {
        try {
            // Check if LCP exists
            $lcpExists = $this->lcp->getById($id);
            if (!$lcpExists) {
                return [
                    'success' => false,
                    'message' => 'LCP device not found'
                ];
            }
 
            // Set LCP ID for update
            $this->lcp->id = $id;
 
            // Set LCP properties from data
            $this->lcp->name = $data['name'];
            $this->lcp->model = $data['model'];
            $this->lcp->location = $data['location'];
            $this->lcp->latitude = $data['latitude'];
            $this->lcp->longitude = $data['longitude'];
            $this->lcp->total_ports = $data['total_ports'];
            $this->lcp->status = $data['status'];
            $this->lcp->parent_olt_id = $data['parent_olt_id'];
            $this->lcp->parent_port_id = $data['parent_port_id'];
            $this->lcp->installation_date = $data['installation_date'];
            $this->lcp->notes = $data['notes'];
 
            // Update LCP device
            if ($this->lcp->update()) {
                return [
                    'success' => true,
                    'message' => 'LCP device updated successfully'
                ];
            }
 
            return [
                'success' => false,
                'message' => 'Failed to update LCP device'
            ];
 
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Delete LCP device
     */
    public function deleteLcpDevice($id) {
        try {
            // Check if LCP exists
            $lcpExists = $this->lcp->getById($id);
            if (!$lcpExists) {
                return [
                    'success' => false,
                    'message' => 'LCP device not found'
                ];
            }
 
            // Delete LCP device
            if ($this->lcp->delete($id)) {
                return [
                    'success' => true,
                    'message' => 'LCP device deleted successfully'
                ];
            }
 
            return [
                'success' => false,
                'message' => 'Failed to delete LCP device'
            ];
 
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Get port by ID
     */
    public function getPort($id) {
        $port = $this->lcp->getPortById($id);
        
        if (!$port) {
            return [
                'success' => false,
                'message' => 'Port not found'
            ];
        }
        
        return [
            'success' => true,
            'port' => $port
        ];
    }
 
    /**
     * Update port
     */
    public function updatePort($id, $data) {
        try {
            // Check if port exists
            $port = $this->lcp->getPortById($id);
            if (!$port) {
                return [
                    'success' => false,
                    'message' => 'Port not found'
                ];
            }
 
            // Update port
            if ($this->lcp->updatePort($id, $data)) {
                return [
                    'success' => true,
                    'message' => 'Port updated successfully'
                ];
            }
 
            return [
                'success' => false,
                'message' => 'Failed to update port'
            ];
 
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Get available OLT ports for LCP connection
     */
    public function getAvailableOltPorts($oltId = null) {
        try {
            $ports = $this->lcp->getAvailableOltPorts($oltId);
            
            return [
                'success' => true,
                'ports' => $ports
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Add maintenance record
     */
    public function addMaintenance($data) {
        try {
            // Validate required fields
            if (empty($data['lcp_id']) || !is_numeric($data['lcp_id'])) {
                return [
                    'success' => false,
                    'message' => 'Valid LCP ID is required'
                ];
            }
            
            if (empty($data['maintenance_type']) || !in_array($data['maintenance_type'], ['preventive', 'corrective', 'inspection'])) {
                return [
                    'success' => false,
                    'message' => 'Valid maintenance type is required'
                ];
            }
            
            if (empty($data['start_date'])) {
                return [
                    'success' => false,
                    'message' => 'Start date is required'
                ];
            }
            
            if (empty($data['description'])) {
                return [
                    'success' => false,
                    'message' => 'Description is required'
                ];
            }
            
            // Add maintenance record
            if ($this->lcp->addMaintenance($data)) {
                return [
                    'success' => true,
                    'message' => 'Maintenance record added successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to add maintenance record'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Update maintenance record
     */
    public function updateMaintenance($id, $data) {
        try {
            // Check if maintenance record exists
            $maintenance = $this->lcp->getMaintenanceById($id);
            if (!$maintenance) {
                return [
                    'success' => false,
                    'message' => 'Maintenance record not found'
                ];
            }
            
            // Update maintenance record
            if ($this->lcp->updateMaintenance($id, $data)) {
                return [
                    'success' => true,
                    'message' => 'Maintenance record updated successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to update maintenance record'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Delete maintenance record
     */
    public function deleteMaintenance($id) {
        try {
            // Check if maintenance record exists
            $maintenance = $this->lcp->getMaintenanceById($id);
            if (!$maintenance) {
                return [
                    'success' => false,
                    'message' => 'Maintenance record not found'
                ];
            }
            
            // Delete maintenance record
            if ($this->lcp->deleteMaintenance($id)) {
                return [
                    'success' => true,
                    'message' => 'Maintenance record deleted successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to delete maintenance record'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Get LCP stats
     */
    public function getLcpStats() {
        return $this->lcp->getStats();
    }
 
    /**
     * Get LCP port utilization data
     */
    public function getPortUtilization($id) {
        try {
            // Check if LCP exists
            $lcpExists = $this->lcp->getById($id);
            if (!$lcpExists) {
                return [
                    'success' => false,
                    'message' => 'LCP device not found'
                ];
            }
 
            // Get port utilization
            $utilization = $this->lcp->getPortUtilization($id);
            
            return [
                'success' => true,
                'utilization' => $utilization
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Get connected clients to LCP
     */
    public function getConnectedClients($id) {
        try {
            // Check if LCP exists
            $lcpExists = $this->lcp->getById($id);
            if (!$lcpExists) {
                return [
                    'success' => false,
                    'message' => 'LCP device not found'
                ];
            }
 
            // Get connected clients
            $clients = $this->lcp->getConnectedClients($id);
            
            return [
                'success' => true,
                'clients' => $clients
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Get maintenance history for an LCP
     */
    public function getMaintenanceHistory($id, $limit = 10) {
        try {
            // Check if LCP exists
            $lcpExists = $this->lcp->getById($id);
            if (!$lcpExists) {
                return [
                    'success' => false,
                    'message' => 'LCP device not found'
                ];
            }
 
            // Get maintenance history
            $history = $this->lcp->getMaintenanceHistory($id, $limit);
            
            return [
                'success' => true,
                'history' => $history
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Get spatial data for all LCPs (for map view)
     */
    public function getSpatialData() {
        try {
            $data = $this->lcp->getSpatialData();
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Find nearest LCPs to coordinates
     */
    public function findNearestLcps($latitude, $longitude, $limit = 5) {
        try {
            if (empty($latitude) || empty($longitude)) {
                return [
                    'success' => false,
                    'message' => 'Latitude and longitude are required'
                ];
            }
            
            $data = $this->lcp->findNearest($latitude, $longitude, $limit);
            
            return [
                'success' => true,
                'lcps' => $data
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Get LCP logs
     */
    public function getLcpLogs($id) {
        try {
            // Check if LCP exists
            $lcpExists = $this->lcp->getById($id);
            if (!$lcpExists) {
                return [
                    'success' => false,
                    'message' => 'LCP device not found'
                ];
                }
 
            // In a real implementation, this would get logs from a logs table
            // For now, return a sample structure
            $logs = [
                ['date' => date('Y-m-d H:i:s', strtotime('-2 days')), 'event' => 'status_change', 'description' => 'Status changed to active'],
                ['date' => date('Y-m-d H:i:s', strtotime('-5 days')), 'event' => 'port_update', 'description' => 'Port 3 changed to active'],
                ['date' => date('Y-m-d H:i:s', strtotime('-7 days')), 'event' => 'connection', 'description' => 'Connected to OLT'],
                ['date' => date('Y-m-d H:i:s', strtotime('-10 days')), 'event' => 'installation', 'description' => 'Device installed']
            ];
            
            return [
                'success' => true,
                'logs' => $logs
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
 
    /**
     * Get LCP ports
     */
    public function getLcpPorts($id) {
        try {
            // Check if LCP exists
            $lcpExists = $this->lcp->getById($id);
            if (!$lcpExists) {
                return [
                    'success' => false,
                    'message' => 'LCP device not found'
                ];
            }
 
            // In a real implementation, this would get ports from a ports table
            // For now, return sample data
            $total_ports = $lcpExists['total_ports'] ?? 8;
            $ports = [];
            
            for ($i = 1; $i <= $total_ports; $i++) {
                $status = $i <= 3 ? 'active' : ($i == 4 ? 'fault' : ($i == 5 ? 'reserved' : 'inactive'));
                $ports[] = [
                    'id' => $i,
                    'port_number' => $i,
                    'port_type' => 'Standard',
                    'status' => $status,
                    'signal_strength' => $status == 'active' ? rand(-30, -15) : null,
                    'client_name' => $status == 'active' ? 'Client ' . $i : null
                ];
            }
            
            return [
                'success' => true,
                'ports' => $ports
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
?>