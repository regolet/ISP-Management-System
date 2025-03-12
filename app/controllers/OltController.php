<?php
namespace App\Controllers;

require_once dirname(__DIR__) . '/Models/Olt.php';

class OltController {
    private $db;
    private $olt;

    public function __construct($db) {
        $this->db = $db;
        $this->olt = new \App\Models\Olt($db);
    }

    /**
     * Get all OLT devices with optional filtering and pagination
     */
    public function getOltDevices($params = []) {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $per_page = isset($params['per_page']) ? (int)$params['per_page'] : 10;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? '';
        $sort = $params['sort'] ?? 'id';
        $order = $params['order'] ?? 'ASC';

        $oltDevices = $this->olt->getAll($page, $per_page, $search, $status, $sort, $order);
        $total = $this->olt->getTotal($search, $status);

        return [
            'data' => $oltDevices,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }

    /**
     * Get OLT device by ID with ports and logs
     */
    public function getOltDevice($id) {
        $oltData = $this->olt->getById($id);
        
        if (!$oltData) {
            return [
                'success' => false,
                'message' => 'OLT device not found'
            ];
        }
        
        return [
            'success' => true,
            'olt' => $oltData
        ];
    }

    /**
     * Create new OLT device
     */
    public function createOltDevice($data) {
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
            
            if (empty($data['ip_address'])) {
                return [
                    'success' => false,
                    'message' => 'IP address is required'
                ];
            }
            
            if (empty($data['total_ports']) || !is_numeric($data['total_ports'])) {
                return [
                    'success' => false,
                    'message' => 'Valid total ports number is required'
                ];
            }

            // Set OLT properties
            foreach ($data as $key => $value) {
                if (property_exists($this->olt, $key)) {
                    $this->olt->$key = $value;
                }
            }

            // Create OLT device
            if ($this->olt->create()) {
                return [
                    'success' => true,
                    'message' => 'OLT device created successfully',
                    'olt_id' => $this->olt->id
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create OLT device'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update existing OLT device
     */
    public function updateOltDevice($id, $data) {
        try {
            // Check if OLT exists
            $oltExists = $this->olt->getById($id);
            if (!$oltExists) {
                return [
                    'success' => false,
                    'message' => 'OLT device not found'
                ];
            }

            // Set OLT ID for update
            $this->olt->id = $id;

            // Set OLT properties
            foreach ($data as $key => $value) {
                if (property_exists($this->olt, $key)) {
                    $this->olt->$key = $value;
                }
            }

            // Update OLT device
            if ($this->olt->update()) {
                return [
                    'success' => true,
                    'message' => 'OLT device updated successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to update OLT device'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete OLT device
     */
    public function deleteOltDevice($id) {
        try {
            // Check if OLT exists
            $oltExists = $this->olt->getById($id);
            if (!$oltExists) {
                return [
                    'success' => false,
                    'message' => 'OLT device not found'
                ];
            }

            // Delete OLT device
            if ($this->olt->delete($id)) {
                return [
                    'success' => true,
                    'message' => 'OLT device deleted successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to delete OLT device'
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
        $port = $this->olt->getPortById($id);
        
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
            $port = $this->olt->getPortById($id);
            if (!$port) {
                return [
                    'success' => false,
                    'message' => 'Port not found'
                ];
            }

            // Update port
            if ($this->olt->updatePort($id, $data)) {
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
     * Sync with OLT device
     */
    public function syncWithDevice($id) {
        try {
            // Check if OLT exists
            $oltExists = $this->olt->getById($id);
            if (!$oltExists) {
                return [
                    'success' => false,
                    'message' => 'OLT device not found'
                ];
            }

            // Sync with device
            if ($this->olt->syncWithDevice($id)) {
                return [
                    'success' => true,
                    'message' => 'OLT device synced successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to sync with OLT device'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get diagnostics for OLT device
     */
    public function getDiagnostics($id) {
        try {
            // Check if OLT exists
            $oltExists = $this->olt->getById($id);
            if (!$oltExists) {
                return [
                    'success' => false,
                    'message' => 'OLT device not found'
                ];
            }

            // Get diagnostics
            $diagnostics = $this->olt->getDiagnostics($id);
            
            return [
                'success' => true,
                'diagnostics' => $diagnostics
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get port utilization statistics
     */
    public function getPortUtilization($id) {
        try {
            // Check if OLT exists
            $oltExists = $this->olt->getById($id);
            if (!$oltExists) {
                return [
                    'success' => false,
                    'message' => 'OLT device not found'
                ];
            }

            // Get port utilization
            $utilization = $this->olt->getPortUtilization($id);
            
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
     * Get OLT statistics
     */
    public function getOltStats() {
        return $this->olt->getStats();
    }

    /**
     * Get all OLTs
     */
    public function getOlts() {
        $query = "SELECT * FROM olt_devices";
        $stmt = $this->db->prepare($query);

        try {
            $stmt->execute();
            $olts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($olts) {
                return ['success' => true, 'data' => $olts];
            } else {
                return ['success' => false, 'message' => 'No OLTs found'];
            }
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
?>

