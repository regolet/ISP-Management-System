<?php
namespace App\Models\Asset;

use App\Core\Model;

class AssetCollection extends Model {
    protected $table = 'asset_collections';
    protected $primaryKey = 'id';
    protected $fillable = [
        'asset_id',
        'user_id',
        'collection_date',
        'return_date',
        'purpose',
        'condition_on_collection',
        'condition_on_return',
        'notes',
        'status',
        'created_at',
        'updated_at'
    ];

    // Get collection details with asset and user information
    public function getDetails() {
        $sql = "SELECT ac.*, 
                       a.name as asset_name, 
                       a.serial_number,
                       u.name as collected_by,
                       u.email as user_email
                FROM {$this->table} ac
                LEFT JOIN assets a ON ac.asset_id = a.id
                LEFT JOIN users u ON ac.user_id = u.id
                WHERE ac.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $this->id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Record asset return
    public function recordReturn($condition, $notes = '') {
        $sql = "UPDATE {$this->table} 
                SET return_date = NOW(),
                    condition_on_return = ?,
                    notes = CONCAT(notes, '\nReturn Notes: ', ?),
                    status = 'returned',
                    updated_at = NOW()
                WHERE id = ? AND status = 'collected'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssi', $condition, $notes, $this->id);
        
        if ($stmt->execute()) {
            // Update asset status to available
            $sql = "UPDATE assets 
                    SET status = 'available', 
                        updated_at = NOW() 
                    WHERE id = (SELECT asset_id FROM {$this->table} WHERE id = ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $this->id);
            return $stmt->execute();
        }
        
        return false;
    }

    // Get active collections for a user
    public function getActiveCollectionsByUser($userId) {
        $sql = "SELECT ac.*, 
                       a.name as asset_name,
                       a.serial_number
                FROM {$this->table} ac
                LEFT JOIN assets a ON ac.asset_id = a.id
                WHERE ac.user_id = ? 
                AND ac.status = 'collected'
                ORDER BY ac.collection_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get collection history for an asset
    public function getCollectionHistoryByAsset($assetId) {
        $sql = "SELECT ac.*, 
                       u.name as collected_by,
                       u.email as user_email
                FROM {$this->table} ac
                LEFT JOIN users u ON ac.user_id = u.id
                WHERE ac.asset_id = ?
                ORDER BY ac.collection_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $assetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Create new collection record
    public function createCollection($data) {
        // First check if asset is available
        $sql = "SELECT status FROM assets WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $data['asset_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result || $result['status'] !== 'available') {
            throw new \Exception('Asset is not available for collection');
        }

        // Begin transaction
        $this->db->getConnection()->begin_transaction();

        try {
            // Create collection record
            $success = $this->create([
                'asset_id' => $data['asset_id'],
                'user_id' => $data['user_id'],
                'collection_date' => date('Y-m-d H:i:s'),
                'purpose' => $data['purpose'],
                'condition_on_collection' => $data['condition'],
                'notes' => $data['notes'] ?? '',
                'status' => 'collected',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if ($success) {
                // Update asset status
                $sql = "UPDATE assets 
                        SET status = 'collected', 
                            updated_at = NOW() 
                        WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('i', $data['asset_id']);
                $stmt->execute();

                $this->db->getConnection()->commit();
                return true;
            }

            throw new \Exception('Failed to create collection record');

        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            throw $e;
        }
    }

    // Validate collection data
    public function validate($data) {
        $errors = [];

        if (empty($data['asset_id'])) {
            $errors['asset_id'] = 'Asset is required';
        }

        if (empty($data['user_id'])) {
            $errors['user_id'] = 'User is required';
        }

        if (empty($data['purpose'])) {
            $errors['purpose'] = 'Purpose is required';
        }

        if (empty($data['condition'])) {
            $errors['condition'] = 'Asset condition is required';
        }

        return $errors;
    }
}
