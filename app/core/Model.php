<?php
namespace App\Core;

class Model 
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];

    public function __construct() 
    {
        $this->db = Application::getInstance()->getDB();
    }

    /**
     * Find record by ID
     */
    public function find($id) 
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all records
     */
    public function all() 
    {
        $sql = "SELECT * FROM {$this->table}";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Create new record
     */
    public function create($data) 
    {
        // Filter fillable fields
        $data = array_intersect_key($data, array_flip($this->fillable));
        
        if (empty($data)) {
            return false;
        }

        // Build query
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($values)";

        // Prepare and execute
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('s', count($data));
        $stmt->bind_param($types, ...array_values($data));
        
        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->insert_id;
    }

    /**
     * Update record
     */
    public function update($id, $data) 
    {
        // Filter fillable fields
        $data = array_intersect_key($data, array_flip($this->fillable));
        
        if (empty($data)) {
            return false;
        }

        // Build query
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$this->table} SET $set WHERE {$this->primaryKey} = ?";

        // Prepare and execute
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('s', count($data)) . 'i';
        $values = array_values($data);
        $values[] = $id;
        $stmt->bind_param($types, ...$values);

        return $stmt->execute();
    }

    /**
     * Delete record
     */
    public function delete($id) 
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    /**
     * Find records by field value
     */
    public function findBy($field, $value) 
    {
        $sql = "SELECT * FROM {$this->table} WHERE $field = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $value);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Find one record by field value
     */
    public function findOneBy($field, $value) 
    {
        $sql = "SELECT * FROM {$this->table} WHERE $field = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $value);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get paginated records
     */
    public function paginate($page = 1, $perPage = 10) 
    {
        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // Get total count
        $total = $this->db->query("SELECT COUNT(*) as count FROM {$this->table}")
                         ->fetch_assoc()['count'];

        // Get records
        $sql = "SELECT * FROM {$this->table} LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $perPage, $offset);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Count records
     */
    public function count() 
    {
        return $this->db->query("SELECT COUNT(*) as count FROM {$this->table}")
                       ->fetch_assoc()['count'];
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() 
    {
        $this->db->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit() 
    {
        $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() 
    {
        $this->db->rollback();
    }

    /**
     * Execute raw query
     */
    public function query($sql, $params = []) 
    {
        if (empty($params)) {
            return $this->db->query($sql);
        }

        $stmt = $this->db->prepare($sql);
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() 
    {
        return $this->db->insert_id;
    }

    /**
     * Get affected rows
     */
    public function affectedRows() 
    {
        return $this->db->affected_rows;
    }

    /**
     * Escape string
     */
    public function escape($string) 
    {
        return $this->db->real_escape_string($string);
    }

    /**
     * Get table name
     */
    public function getTable() 
    {
        return $this->table;
    }

    /**
     * Get primary key
     */
    public function getPrimaryKey() 
    {
        return $this->primaryKey;
    }

    /**
     * Get fillable fields
     */
    public function getFillable() 
    {
        return $this->fillable;
    }

    /**
     * Get hidden fields
     */
    public function getHidden() 
    {
        return $this->hidden;
    }
}
