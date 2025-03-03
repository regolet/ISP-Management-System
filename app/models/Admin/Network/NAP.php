<?php
namespace App\Models\Network;

use App\Core\Model;

class NAP extends Model {
    protected $table = 'nap_boxes';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'lcp_id',
        'location',
        'coordinates',
        'total_ports',
        'splitter_type',
        'installation_date',
        'status',
        'enclosure_type',
        'mounting_type',
        'maintenance_date',
        'notes',
        'created_at',
        'updated_at'
    ];

    // Get NAP details with LCP information
    public function getDetails() {
        $sql = "SELECT n.*,
                       l.name as lcp_name,
                       l.location as lcp_location,
                       COUNT(p.id) as used_ports,
                       GROUP_CONCAT(DISTINCT o.serial_number) as connected_onus
                FROM {$this->table} n
                LEFT JOIN lcps l ON n.lcp_id = l.id
                LEFT JOIN nap_ports p ON n.id = p.nap_id
                LEFT JOIN onus o ON p.onu_id = o.id
                WHERE n.id = ?
                GROUP BY n.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Get port utilization
    public function getPortUtilization() {
        $sql = "SELECT 
                    p.port_number,
                    p.status,
                    p.fiber_color,
                    o.serial_number as onu_serial,
                    o.status as onu_status,
                    c.name as customer_name,
                    c.id as customer_id,
                    m.date as last_maintenance,
                    m.technician as last_technician
                FROM nap_ports p
                LEFT JOIN onus o ON p.onu_id = o.id
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN (
                    SELECT port_id, MAX(date) as date, technician
                    FROM port_maintenance_history
                    GROUP BY port_id
                ) m ON p.id = m.port_id
                WHERE p.nap_id = ?
                ORDER BY p.port_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Add new port
    public function addPort($portNumber, $data = []) {
        // Check if port number already exists
        $sql = "SELECT id FROM nap_ports WHERE nap_id = ? AND port_number = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $this->id, $portNumber);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new \Exception('Port number already exists');
        }

        $sql = "INSERT INTO nap_ports (
                    nap_id,
                    port_number,
                    status,
                    fiber_color,
                    notes,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iisss',
            $this->id,
            $portNumber,
            $data['status'] ?? 'available',
            $data['fiber_color'] ?? '',
            $data['notes'] ?? ''
        );
        return $stmt->execute();
    }

    // Connect ONU to port
    public function connectONU($portNumber, $onuId) {
        // Begin transaction
        $this->db->getConnection()->begin_transaction();

        try {
            // Check if port is available
            $sql = "SELECT id, status FROM nap_ports 
                    WHERE nap_id = ? AND port_number = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $this->id, $portNumber);
            $stmt->execute();
            $port = $stmt->get_result()->fetch_assoc();

            if (!$port) {
                throw new \Exception('Port not found');
            }
            if ($port['status'] !== 'available') {
                throw new \Exception('Port is not available');
            }

            // Update port status and connect ONU
            $sql = "UPDATE nap_ports 
                    SET onu_id = ?,
                        status = 'connected',
                        updated_at = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $onuId, $port['id']);
            $stmt->execute();

            // Log the connection
            $sql = "INSERT INTO port_connection_history (
                        port_id,
                        onu_id,
                        action,
                        date
                    ) VALUES (?, ?, 'connect', NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $port['id'], $onuId);
            $stmt->execute();

            $this->db->getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            throw $e;
        }
    }

    // Disconnect ONU from port
    public function disconnectONU($portNumber) {
        // Begin transaction
        $this->db->getConnection()->begin_transaction();

        try {
            // Get port and ONU details
            $sql = "SELECT p.id, p.onu_id 
                    FROM nap_ports p
                    WHERE p.nap_id = ? AND p.port_number = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $this->id, $portNumber);
            $stmt->execute();
            $port = $stmt->get_result()->fetch_assoc();

            if (!$port || !$port['onu_id']) {
                throw new \Exception('No ONU connected to this port');
            }

            // Log the disconnection
            $sql = "INSERT INTO port_connection_history (
                        port_id,
                        onu_id,
                        action,
                        date
                    ) VALUES (?, ?, 'disconnect', NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $port['id'], $port['onu_id']);
            $stmt->execute();

            // Update port status
            $sql = "UPDATE nap_ports 
                    SET onu_id = NULL,
                        status = 'available',
                        updated_at = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $port['id']);
            $stmt->execute();

            $this->db->getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            throw $e;
        }
    }

    // Record port maintenance
    public function recordPortMaintenance($portNumber, $data) {
        $sql = "INSERT INTO port_maintenance_history (
                    port_id,
                    date,
                    technician,
                    work_performed,
                    findings,
                    next_maintenance
                ) VALUES (
                    (SELECT id FROM nap_ports WHERE nap_id = ? AND port_number = ?),
                    NOW(),
                    ?,
                    ?,
                    ?,
                    ?
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iissss',
            $this->id,
            $portNumber,
            $data['technician'],
            $data['work_performed'],
            $data['findings'],
            $data['next_maintenance']
        );
        return $stmt->execute();
    }

    // Validate NAP data
    public function validate($data) {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'NAP name is required';
        }

        if (empty($data['lcp_id'])) {
            $errors['lcp_id'] = 'LCP is required';
        }

        if (empty($data['total_ports'])) {
            $errors['total_ports'] = 'Total ports is required';
        } elseif (!is_numeric($data['total_ports']) || $data['total_ports'] <= 0) {
            $errors['total_ports'] = 'Invalid number of ports';
        }

        if (!empty($data['coordinates'])) {
            if (!preg_match('/^-?\d+\.?\d*,\s*-?\d+\.?\d*$/', $data['coordinates'])) {
                $errors['coordinates'] = 'Invalid coordinates format (latitude,longitude)';
            }
        }

        if (empty($data['splitter_type'])) {
            $errors['splitter_type'] = 'Splitter type is required';
        }

        return $errors;
    }
}
