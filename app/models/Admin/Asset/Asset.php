<?php
namespace App\Models\Admin\Asset;

use App\Core\Model;

class Asset extends Model 
{
    protected $table = 'assets';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'description',
        'address',
        'asset_type',
        'purchase_date',
        'purchase_price',
        'current_value',
        'expected_amount',
        'next_collection_date',
        'location',
        'status',
        'serial_number',
        'warranty_expiry',
        'maintenance_schedule',
        'notes',
        'created_at',
        'updated_at'
    ];

    /**
     * Get asset statistics
     * @return array
     */
    public function getStatistics(): array 
    {
        $sql = "
            SELECT 
                COUNT(id) as total_assets,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_assets,
                COUNT(CASE WHEN next_collection_date <= CURRENT_DATE() AND status = 'active' THEN 1 END) as due_collections
            FROM {$this->table}
        ";
        
        $result = $this->db->query($sql);
        return $result->fetch_assoc();
    }

    /**
     * Get collection statistics for a date range
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getCollectionStatistics(string $startDate, string $endDate): array 
    {
        $sql = "
            SELECT 
                COALESCE(SUM(ac.amount), 0) as total_collections,
                COUNT(DISTINCT ac.asset_id) as collected_assets
            FROM asset_collections ac
            INNER JOIN {$this->table} a ON ac.asset_id = a.id
            WHERE ac.collection_date BETWEEN ? AND ?
            AND a.status = 'active'
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get uncollected statistics for a date range
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getUncollectedStatistics(string $startDate, string $endDate): array 
    {
        $sql = "
            SELECT 
                COALESCE(SUM(a.expected_amount), 0) as total_uncollected,
                COUNT(a.id) as pending_assets
            FROM {$this->table} a
            WHERE a.status = 'active'
            AND a.next_collection_date BETWEEN ? AND ?
            AND NOT EXISTS (
                SELECT 1 FROM asset_collections ac 
                WHERE ac.asset_id = a.id 
                AND ac.collection_date BETWEEN ? AND ?
            )
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssss', $startDate, $endDate, $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Search assets with filters
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function searchAssets(array $filters = [], int $page = 1, int $perPage = 10): array 
    {
        $baseQuery = "
            SELECT a.*, 
            COALESCE((SELECT COUNT(*) FROM asset_collections WHERE asset_id = a.id), 0) as collection_count,
            COALESCE((SELECT SUM(amount) FROM asset_collections WHERE asset_id = a.id), 0) as total_collected
            FROM {$this->table} a WHERE 1=1
        ";
        
        $whereConditions = [];
        $params = [];
        $types = "";

        if (!empty($filters['search'])) {
            $search = "%" . $filters['search'] . "%";
            $whereConditions[] = "(name LIKE ? OR address LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $types .= "ss";
        }

        if (!empty($filters['status'])) {
            $whereConditions[] = "status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        if (!empty($whereConditions)) {
            $baseQuery .= " AND " . implode(" AND ", $whereConditions);
        }

        // Get total count
        $countStmt = $this->db->prepare($baseQuery);
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalRecords = $countStmt->get_result()->num_rows;

        // Add pagination
        $offset = ($page - 1) * $perPage;
        $baseQuery .= " ORDER BY name LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $perPage;
        $types .= "ii";

        $stmt = $this->db->prepare($baseQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $assets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'assets' => $assets,
            'total' => $totalRecords,
            'pages' => ceil($totalRecords / $perPage),
            'current_page' => $page
        ];
    }

    /**
     * Update asset status
     * @param string $status
     * @return bool
     */
    public function updateStatus(string $status): bool 
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $status, $this->id);
        return $stmt->execute();
    }

    // ... (keep existing methods from the previous version)
}
