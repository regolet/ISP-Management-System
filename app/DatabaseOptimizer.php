<?php

namespace App;

/**
 * Database Optimizer - Provides query optimization and caching
 */
class DatabaseOptimizer {
    private $db;
    private $cache = [];
    private $queryLog = [];
    private $slowQueryThreshold = 1.0; // seconds

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Execute optimized query with caching
     */
    public function executeQuery($query, $params = [], $cacheKey = null, $cacheTime = 300) {
        $startTime = microtime(true);
        
        // Check cache first
        if ($cacheKey && isset($this->cache[$cacheKey])) {
            $cacheData = $this->cache[$cacheKey];
            if (time() - $cacheData['time'] < $cacheTime) {
                return $cacheData['data'];
            }
        }

        // Execute query
        $stmt = $this->db->executeQuery($query, $params);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $executionTime = microtime(true) - $startTime;
        
        // Log slow queries
        if ($executionTime > $this->slowQueryThreshold) {
            $this->logSlowQuery($query, $params, $executionTime);
        }
        
        // Cache result if cache key provided
        if ($cacheKey) {
            $this->cache[$cacheKey] = [
                'data' => $result,
                'time' => time()
            ];
        }
        
        // Log query for debugging
        $this->logQuery($query, $params, $executionTime);
        
        return $result;
    }

    /**
     * Get paginated results with optimized count query
     */
    public function getPaginatedResults($table, $conditions = [], $page = 1, $perPage = 10, $orderBy = 'id', $order = 'ASC') {
        $offset = ($page - 1) * $perPage;
        
        // Build WHERE clause
        $whereClause = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $field => $value) {
                $whereParts[] = "$field = ?";
                $params[] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        }
        
        // Get total count (cached)
        $countCacheKey = "count_{$table}_" . md5(serialize($conditions));
        $countQuery = "SELECT COUNT(*) as total FROM $table $whereClause";
        $countResult = $this->executeQuery($countQuery, $params, $countCacheKey, 60);
        $total = $countResult[0]['total'] ?? 0;
        
        // Get paginated data
        $dataQuery = "SELECT * FROM $table $whereClause ORDER BY $orderBy $order LIMIT ?, ?";
        $dataParams = array_merge($params, [$offset, $perPage]);
        $data = $this->executeQuery($dataQuery, $dataParams);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get single record with caching
     */
    public function getById($table, $id, $cacheTime = 300) {
        $cacheKey = "{$table}_by_id_{$id}";
        $query = "SELECT * FROM $table WHERE id = ?";
        
        $result = $this->executeQuery($query, [$id], $cacheKey, $cacheTime);
        return $result[0] ?? null;
    }

    /**
     * Batch insert with transaction
     */
    public function batchInsert($table, $data, $batchSize = 100) {
        if (empty($data)) {
            return 0;
        }
        
        $this->db->beginTransaction();
        
        try {
            $inserted = 0;
            $batches = array_chunk($data, $batchSize);
            
            foreach ($batches as $batch) {
                $columns = array_keys($batch[0]);
                $placeholders = '(' . str_repeat('?,', count($columns) - 1) . '?)';
                $query = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES " . 
                        str_repeat($placeholders . ',', count($batch) - 1) . $placeholders;
                
                $params = [];
                foreach ($batch as $row) {
                    $params = array_merge($params, array_values($row));
                }
                
                $stmt = $this->db->executeQuery($query, $params);
                $inserted += $stmt->rowCount();
            }
            
            $this->db->commit();
            return $inserted;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update with cache invalidation
     */
    public function update($table, $id, $data) {
        $setParts = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $setParts[] = "$field = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        $query = "UPDATE $table SET " . implode(',', $setParts) . " WHERE id = ?";
        
        $result = $this->db->executeQuery($query, $params);
        
        // Invalidate cache
        $this->invalidateCache("{$table}_by_id_{$id}");
        $this->invalidateCache("count_{$table}_*");
        
        return $result->rowCount();
    }

    /**
     * Delete with cache invalidation
     */
    public function delete($table, $id) {
        $query = "DELETE FROM $table WHERE id = ?";
        $result = $this->db->executeQuery($query, [$id]);
        
        // Invalidate cache
        $this->invalidateCache("{$table}_by_id_{$id}");
        $this->invalidateCache("count_{$table}_*");
        
        return $result->rowCount();
    }

    /**
     * Search with full-text search optimization
     */
    public function search($table, $searchTerm, $searchFields = [], $page = 1, $perPage = 10) {
        if (empty($searchFields)) {
            $searchFields = ['name', 'description'];
        }
        
        $offset = ($page - 1) * $perPage;
        $params = [];
        $whereParts = [];
        
        foreach ($searchFields as $field) {
            $whereParts[] = "$field LIKE ?";
            $params[] = "%$searchTerm%";
        }
        
        $whereClause = 'WHERE ' . implode(' OR ', $whereParts);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM $table $whereClause";
        $countResult = $this->executeQuery($countQuery, $params);
        $total = $countResult[0]['total'] ?? 0;
        
        // Get paginated results
        $dataQuery = "SELECT * FROM $table $whereClause ORDER BY id DESC LIMIT ?, ?";
        $dataParams = array_merge($params, [$offset, $perPage]);
        $data = $this->executeQuery($dataQuery, $dataParams);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get statistics with caching
     */
    public function getStats($table, $conditions = []) {
        $cacheKey = "stats_{$table}_" . md5(serialize($conditions));
        
        $whereClause = '';
        $params = [];
        
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $field => $value) {
                $whereParts[] = "$field = ?";
                $params[] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        }
        
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
                    SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline
                  FROM $table $whereClause";
        
        $result = $this->executeQuery($query, $params, $cacheKey, 300);
        return $result[0] ?? ['total' => 0, 'active' => 0, 'maintenance' => 0, 'offline' => 0];
    }

    /**
     * Clear cache
     */
    public function clearCache($pattern = null) {
        if ($pattern) {
            foreach (array_keys($this->cache) as $key) {
                if (fnmatch($pattern, $key)) {
                    unset($this->cache[$key]);
                }
            }
        } else {
            $this->cache = [];
        }
    }

    /**
     * Invalidate cache by pattern
     */
    public function invalidateCache($pattern) {
        $this->clearCache($pattern);
    }

    /**
     * Get query performance statistics
     */
    public function getQueryStats() {
        $totalQueries = count($this->queryLog);
        $slowQueries = array_filter($this->queryLog, function($log) {
            return $log['execution_time'] > $this->slowQueryThreshold;
        });
        
        $totalTime = array_sum(array_column($this->queryLog, 'execution_time'));
        $avgTime = $totalQueries > 0 ? $totalTime / $totalQueries : 0;
        
        return [
            'total_queries' => $totalQueries,
            'slow_queries' => count($slowQueries),
            'total_time' => $totalTime,
            'average_time' => $avgTime,
            'slow_query_threshold' => $this->slowQueryThreshold
        ];
    }

    /**
     * Log slow queries
     */
    private function logSlowQuery($query, $params, $executionTime) {
        $log = [
            'query' => $query,
            'params' => $params,
            'execution_time' => $executionTime,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        error_log("SLOW QUERY: " . json_encode($log));
    }

    /**
     * Log query for debugging
     */
    private function logQuery($query, $params, $executionTime) {
        $this->queryLog[] = [
            'query' => $query,
            'params' => $params,
            'execution_time' => $executionTime,
            'timestamp' => microtime(true)
        ];
        
        // Keep only last 1000 queries in memory
        if (count($this->queryLog) > 1000) {
            $this->queryLog = array_slice($this->queryLog, -1000);
        }
    }

    /**
     * Optimize database tables
     */
    public function optimizeTables($tables = []) {
        if (empty($tables)) {
            // Get all tables
            $result = $this->db->executeQuery("SELECT name FROM sqlite_master WHERE type='table'");
            $tables = array_column($result->fetchAll(\PDO::FETCH_ASSOC), 'name');
        }
        
        foreach ($tables as $table) {
            try {
                $this->db->executeQuery("VACUUM $table");
                $this->db->executeQuery("ANALYZE $table");
            } catch (\Exception $e) {
                error_log("Failed to optimize table $table: " . $e->getMessage());
            }
        }
    }

    /**
     * Create indexes for better performance
     */
    public function createIndexes() {
        $indexes = [
            // OLT devices
            "CREATE INDEX IF NOT EXISTS idx_olt_devices_status ON olt_devices(status)",
            "CREATE INDEX IF NOT EXISTS idx_olt_devices_location ON olt_devices(location)",
            "CREATE INDEX IF NOT EXISTS idx_olt_devices_name ON olt_devices(name)",
            
            // OLT ports
            "CREATE INDEX IF NOT EXISTS idx_olt_ports_olt_id ON olt_ports(olt_id)",
            "CREATE INDEX IF NOT EXISTS idx_olt_ports_status ON olt_ports(status)",
            "CREATE INDEX IF NOT EXISTS idx_olt_ports_port_number ON olt_ports(port_number)",
            
            // Clients
            "CREATE INDEX IF NOT EXISTS idx_clients_email ON clients(email)",
            "CREATE INDEX IF NOT EXISTS idx_clients_status ON clients(status)",
            "CREATE INDEX IF NOT EXISTS idx_clients_created_at ON clients(created_at)",
            
            // Subscriptions
            "CREATE INDEX IF NOT EXISTS idx_subscriptions_client_id ON client_subscriptions(client_id)",
            "CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON client_subscriptions(status)",
            "CREATE INDEX IF NOT EXISTS idx_subscriptions_start_date ON client_subscriptions(start_date)",
            
            // Invoices
            "CREATE INDEX IF NOT EXISTS idx_invoices_client_id ON invoices(client_id)",
            "CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status)",
            "CREATE INDEX IF NOT EXISTS idx_invoices_due_date ON invoices(due_date)",
            
            // Payments
            "CREATE INDEX IF NOT EXISTS idx_payments_invoice_id ON payments(invoice_id)",
            "CREATE INDEX IF NOT EXISTS idx_payments_status ON payments(status)",
            "CREATE INDEX IF NOT EXISTS idx_payments_created_at ON payments(created_at)"
        ];
        
        foreach ($indexes as $index) {
            try {
                $this->db->executeQuery($index);
            } catch (\Exception $e) {
                error_log("Failed to create index: " . $e->getMessage());
            }
        }
    }
}