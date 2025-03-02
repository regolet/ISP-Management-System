<?php
namespace App\Models\Network;

use App\Core\Model;

class ONU extends Model {
    protected $table = 'onus';
    protected $primaryKey = 'id';
    protected $fillable = [
        'serial_number',
        'pon_port_id',
        'customer_id',
        'model',
        'mac_address',
        'status',
        'rx_power',
        'tx_power',
        'temperature',
        'firmware_version',
        'configuration',
        'service_profile',
        'installation_date',
        'last_seen',
        'notes',
        'created_at',
        'updated_at'
    ];

    // Get ONU details with related information
    public function getDetails() {
        $sql = "SELECT o.*,
                       p.port_number,
                       p.olt_id,
                       olt.name as olt_name,
                       olt.ip_address as olt_ip,
                       c.name as customer_name,
                       c.address as installation_address,
                       c.phone as customer_phone,
                       s.name as service_name,
                       s.bandwidth_up,
                       s.bandwidth_down
                FROM {$this->table} o
                LEFT JOIN pon_ports p ON o.pon_port_id = p.id
                LEFT JOIN olt_devices olt ON p.olt_id = olt.id
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN service_profiles s ON o.service_profile = s.id
                WHERE o.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Get signal history
    public function getSignalHistory($days = 7) {
        $sql = "SELECT 
                    timestamp,
                    rx_power,
                    tx_power,
                    temperature,
                    status
                FROM onu_signal_history
                WHERE onu_id = ?
                AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY timestamp DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $this->id, $days);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Record signal levels
    public function recordSignalLevels($rx_power, $tx_power, $temperature, $status) {
        $sql = "INSERT INTO onu_signal_history (
                    onu_id,
                    rx_power,
                    tx_power,
                    temperature,
                    status,
                    timestamp
                ) VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iddds', 
            $this->id,
            $rx_power,
            $tx_power,
            $temperature,
            $status
        );
        
        if ($stmt->execute()) {
            // Update current values in ONU table
            $sql = "UPDATE {$this->table} 
                    SET rx_power = ?,
                        tx_power = ?,
                        temperature = ?,
                        status = ?,
                        last_seen = NOW(),
                        updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('dddsi',
                $rx_power,
                $tx_power,
                $temperature,
                $status,
                $this->id
            );
            return $stmt->execute();
        }
        
        return false;
    }

    // Update service profile
    public function updateServiceProfile($profileId) {
        $sql = "UPDATE {$this->table} 
                SET service_profile = ?,
                    updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $profileId, $this->id);
        return $stmt->execute();
    }

    // Get configuration history
    public function getConfigHistory() {
        $sql = "SELECT 
                    timestamp,
                    configuration,
                    changed_by,
                    change_reason
                FROM onu_config_history
                WHERE onu_id = ?
                ORDER BY timestamp DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Update configuration
    public function updateConfiguration($config, $userId, $reason = '') {
        // Begin transaction
        $this->db->getConnection()->begin_transaction();

        try {
            // Store old configuration in history
            $sql = "INSERT INTO onu_config_history (
                        onu_id,
                        configuration,
                        changed_by,
                        change_reason,
                        timestamp
                    ) VALUES (?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('isis',
                $this->id,
                $this->configuration,
                $userId,
                $reason
            );
            $stmt->execute();

            // Update current configuration
            $sql = "UPDATE {$this->table} 
                    SET configuration = ?,
                        updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $config, $this->id);
            $stmt->execute();

            $this->db->getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            throw $e;
        }
    }

    // Check signal quality
    public function checkSignalQuality() {
        $thresholds = [
            'rx_power' => ['min' => -28, 'max' => -8],  // dBm
            'tx_power' => ['min' => -3, 'max' => 7],    // dBm
            'temperature' => ['min' => 0, 'max' => 70]   // Celsius
        ];

        $issues = [];

        if ($this->rx_power < $thresholds['rx_power']['min']) {
            $issues[] = 'RX power too low';
        } elseif ($this->rx_power > $thresholds['rx_power']['max']) {
            $issues[] = 'RX power too high';
        }

        if ($this->tx_power < $thresholds['tx_power']['min']) {
            $issues[] = 'TX power too low';
        } elseif ($this->tx_power > $thresholds['tx_power']['max']) {
            $issues[] = 'TX power too high';
        }

        if ($this->temperature < $thresholds['temperature']['min']) {
            $issues[] = 'Temperature too low';
        } elseif ($this->temperature > $thresholds['temperature']['max']) {
            $issues[] = 'Temperature too high';
        }

        return [
            'status' => empty($issues) ? 'good' : 'warning',
            'issues' => $issues
        ];
    }

    // Validate ONU data
    public function validate($data) {
        $errors = [];

        if (empty($data['serial_number'])) {
            $errors['serial_number'] = 'Serial number is required';
        }

        if (empty($data['pon_port_id'])) {
            $errors['pon_port_id'] = 'PON port is required';
        }

        if (!empty($data['mac_address'])) {
            if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $data['mac_address'])) {
                $errors['mac_address'] = 'Invalid MAC address format';
            }
        }

        if (!empty($data['rx_power'])) {
            if (!is_numeric($data['rx_power']) || $data['rx_power'] < -40 || $data['rx_power'] > 0) {
                $errors['rx_power'] = 'Invalid RX power value';
            }
        }

        if (!empty($data['tx_power'])) {
            if (!is_numeric($data['tx_power']) || $data['tx_power'] < -10 || $data['tx_power'] > 10) {
                $errors['tx_power'] = 'Invalid TX power value';
            }
        }

        return $errors;
    }
}
