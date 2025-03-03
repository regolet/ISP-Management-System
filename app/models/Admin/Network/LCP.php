<?php
namespace App\Models\Network;

use App\Core\Model;

class LCP extends Model {
    protected $table = 'lcps';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'location',
        'coordinates',
        'total_splitters',
        'splitter_types',
        'feeder_cable',
        'installation_date',
        'status',
        'enclosure_type',
        'maintenance_date',
        'notes',
        'created_at',
        'updated_at'
    ];

    // Get LCP details with connected NAPs
    public function getDetails() {
        $sql = "SELECT l.*,
                       COUNT(DISTINCT n.id) as connected_naps,
                       COUNT(DISTINCT p.id) as total_ports,
                       COUNT(DISTINCT o.id) as connected_onus,
                       GROUP_CONCAT(DISTINCT s.type) as splitter_configuration
                FROM {$this->table} l
                LEFT JOIN nap_boxes n ON l.id = n.lcp_id
                LEFT JOIN nap_ports p ON n.id = p.nap_id
                LEFT JOIN onus o ON p.onu_id = o.id
                LEFT JOIN lcp_splitters s ON l.id = s.lcp_id
                WHERE l.id = ?
                GROUP BY l.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Get connected NAPs
    public function getConnectedNAPs() {
        $sql = "SELECT n.*,
                       COUNT(p.id) as total_ports,
                       SUM(CASE WHEN p.status = 'connected' THEN 1 ELSE 0 END) as used_ports,
                       GROUP_CONCAT(DISTINCT o.serial_number) as connected_onus
                FROM nap_boxes n
                LEFT JOIN nap_ports p ON n.id = p.nap_id
                LEFT JOIN onus o ON p.onu_id = o.id
                WHERE n.lcp_id = ?
                GROUP BY n.id
                ORDER BY n.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get splitter configuration
    public function getSplitterConfiguration() {
        $sql = "SELECT s.*,
                       COUNT(DISTINCT n.id) as connected_naps,
                       SUM(CASE WHEN n.status = 'active' THEN 1 ELSE 0 END) as active_naps
                FROM lcp_splitters s
                LEFT JOIN nap_boxes n ON s.id = n.splitter_id
                WHERE s.lcp_id = ?
                GROUP BY s.id
                ORDER BY s.position";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Add splitter
    public function addSplitter($data) {
        // Begin transaction
        $this->db->getConnection()->begin_transaction();

        try {
            // Check splitter limit
            $sql = "SELECT COUNT(*) as count FROM lcp_splitters WHERE lcp_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $this->id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if ($result['count'] >= $this->total_splitters) {
                throw new \Exception('Maximum number of splitters reached');
            }

            // Add new splitter
            $sql = "INSERT INTO lcp_splitters (
                        lcp_id,
                        type,
                        position,
                        status,
                        installation_date,
                        notes,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, NOW(), ?, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('isiss',
                $this->id,
                $data['type'],
                $data['position'],
                $data['status'] ?? 'active',
                $data['notes'] ?? ''
            );
            $stmt->execute();

            $this->db->getConnection()->commit();
            return $stmt->insert_id;
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            throw $e;
        }
    }

    // Record maintenance
    public function recordMaintenance($data) {
        $sql = "INSERT INTO lcp_maintenance_history (
                    lcp_id,
                    date,
                    technician,
                    work_performed,
                    findings,
                    next_maintenance,
                    created_at
                ) VALUES (?, NOW(), ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issss',
            $this->id,
            $data['technician'],
            $data['work_performed'],
            $data['findings'],
            $data['next_maintenance']
        );

        if ($stmt->execute()) {
            // Update LCP maintenance date
            $sql = "UPDATE {$this->table} 
                    SET maintenance_date = NOW(),
                        updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $this->id);
            return $stmt->execute();
        }

        return false;
    }

    // Get maintenance history
    public function getMaintenanceHistory() {
        $sql = "SELECT * FROM lcp_maintenance_history 
                WHERE lcp_id = ?
                ORDER BY date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Calculate capacity utilization
    public function getCapacityUtilization() {
        $sql = "SELECT 
                    COUNT(DISTINCT n.id) as total_naps,
                    COUNT(DISTINCT p.id) as total_ports,
                    SUM(CASE WHEN p.status = 'connected' THEN 1 ELSE 0 END) as used_ports,
                    COUNT(DISTINCT o.id) as connected_onus,
                    COUNT(DISTINCT s.id) as installed_splitters
                FROM {$this->table} l
                LEFT JOIN nap_boxes n ON l.id = n.lcp_id
                LEFT JOIN nap_ports p ON n.id = p.nap_id
                LEFT JOIN onus o ON p.onu_id = o.id
                LEFT JOIN lcp_splitters s ON l.id = s.lcp_id
                WHERE l.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return [
            'nap_utilization' => [
                'total' => $result['total_naps'],
                'percentage' => $result['total_naps'] > 0 ? 
                    ($result['total_naps'] / $this->total_splitters) * 100 : 0
            ],
            'port_utilization' => [
                'total' => $result['total_ports'],
                'used' => $result['used_ports'],
                'percentage' => $result['total_ports'] > 0 ? 
                    ($result['used_ports'] / $result['total_ports']) * 100 : 0
            ],
            'splitter_utilization' => [
                'total' => $this->total_splitters,
                'installed' => $result['installed_splitters'],
                'percentage' => ($result['installed_splitters'] / $this->total_splitters) * 100
            ],
            'connected_onus' => $result['connected_onus']
        ];
    }

    // Validate LCP data
    public function validate($data) {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'LCP name is required';
        }

        if (empty($data['location'])) {
            $errors['location'] = 'Location is required';
        }

        if (empty($data['total_splitters'])) {
            $errors['total_splitters'] = 'Total splitters is required';
        } elseif (!is_numeric($data['total_splitters']) || $data['total_splitters'] <= 0) {
            $errors['total_splitters'] = 'Invalid number of splitters';
        }

        if (!empty($data['coordinates'])) {
            if (!preg_match('/^-?\d+\.?\d*,\s*-?\d+\.?\d*$/', $data['coordinates'])) {
                $errors['coordinates'] = 'Invalid coordinates format (latitude,longitude)';
            }
        }

        if (empty($data['feeder_cable'])) {
            $errors['feeder_cable'] = 'Feeder cable information is required';
        }

        return $errors;
    }
}
