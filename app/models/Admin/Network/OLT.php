<?php
namespace App\Models\Network;

use App\Core\Model;

class OLT extends Model {
    protected $table = 'olt_devices';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'model',
        'serial_number',
        'ip_address',
        'location',
        'total_pon_ports',
        'uplink_capacity',
        'status',
        'vendor',
        'firmware_version',
        'management_vlan',
        'notes',
        'created_at',
        'updated_at'
    ];

    // Get all PON ports for this OLT
    public function getPonPorts() {
        $sql = "SELECT p.*, 
                       COUNT(o.id) as connected_onus,
                       GROUP_CONCAT(o.serial_number) as onu_serials
                FROM pon_ports p
                LEFT JOIN onus o ON p.id = o.pon_port_id
                WHERE p.olt_id = ?
                GROUP BY p.id
                ORDER BY p.port_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get specific PON port details
    public function getPonPort($portNumber) {
        $sql = "SELECT p.*, 
                       COUNT(o.id) as connected_onus,
                       SUM(CASE WHEN o.status = 'active' THEN 1 ELSE 0 END) as active_onus
                FROM pon_ports p
                LEFT JOIN onus o ON p.id = o.pon_port_id
                WHERE p.olt_id = ? AND p.port_number = ?
                GROUP BY p.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $this->id, $portNumber);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Get all ONUs connected to this OLT
    public function getConnectedONUs() {
        $sql = "SELECT o.*, 
                       p.port_number,
                       c.name as customer_name,
                       c.id as customer_id
                FROM onus o
                LEFT JOIN pon_ports p ON o.pon_port_id = p.id
                LEFT JOIN customers c ON o.customer_id = c.id
                WHERE p.olt_id = ?
                ORDER BY p.port_number, o.serial_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get OLT utilization statistics
    public function getUtilizationStats() {
        $sql = "SELECT 
                    COUNT(DISTINCT p.id) as total_ports,
                    COUNT(DISTINCT o.id) as total_onus,
                    SUM(CASE WHEN o.status = 'active' THEN 1 ELSE 0 END) as active_onus,
                    SUM(CASE WHEN o.status = 'inactive' THEN 1 ELSE 0 END) as inactive_onus,
                    AVG(o.rx_power) as avg_rx_power,
                    MIN(o.rx_power) as min_rx_power,
                    MAX(o.rx_power) as max_rx_power
                FROM pon_ports p
                LEFT JOIN onus o ON p.id = o.pon_port_id
                WHERE p.olt_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Add new PON port
    public function addPonPort($portNumber, $data) {
        // Check if port number already exists
        $sql = "SELECT id FROM pon_ports WHERE olt_id = ? AND port_number = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $this->id, $portNumber);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new \Exception('PON port number already exists');
        }

        $sql = "INSERT INTO pon_ports (
                    olt_id, 
                    port_number, 
                    status,
                    max_onus,
                    notes,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iisis', 
            $this->id,
            $portNumber,
            $data['status'] ?? 'active',
            $data['max_onus'] ?? 64,
            $data['notes'] ?? ''
        );
        return $stmt->execute();
    }

    // Update OLT status
    public function updateStatus($status, $notes = '') {
        $sql = "UPDATE {$this->table} 
                SET status = ?,
                    notes = CONCAT(IFNULL(notes, ''), '\nStatus Update: ', ?),
                    updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssi', $status, $notes, $this->id);
        return $stmt->execute();
    }

    // Validate OLT data
    public function validate($data) {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'OLT name is required';
        }

        if (empty($data['ip_address'])) {
            $errors['ip_address'] = 'IP address is required';
        } elseif (!filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
            $errors['ip_address'] = 'Invalid IP address format';
        }

        if (empty($data['total_pon_ports'])) {
            $errors['total_pon_ports'] = 'Total PON ports is required';
        } elseif (!is_numeric($data['total_pon_ports']) || $data['total_pon_ports'] <= 0) {
            $errors['total_pon_ports'] = 'Invalid number of PON ports';
        }

        if (!empty($data['management_vlan'])) {
            if (!is_numeric($data['management_vlan']) || 
                $data['management_vlan'] < 1 || 
                $data['management_vlan'] > 4094) {
                $errors['management_vlan'] = 'Invalid VLAN ID';
            }
        }

        return $errors;
    }

    // Check if OLT is reachable
    public function checkConnectivity() {
        // This is a placeholder. In a real implementation, you would:
        // 1. Try to ping the OLT
        // 2. Try to connect via SNMP
        // 3. Check management interface
        exec("ping -c 1 " . escapeshellarg($this->ip_address), $output, $result);
        return $result === 0;
    }
}
