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
        $this->db = Application::getInstance()->getDB()->getConnection();
        if ($this->db === null) {
            throw new \Exception("Database connection not established.");
        }
    }

    /**
     * Find record by ID
     */
    public function find($id) 
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Create a new record
     */
    public function create(array $data) 
    {
        // Filter data to only include fillable fields
        $data = array_intersect_key($data, array_flip($this->fillable));
        
        if (empty($data)) {
            return false;
        }

        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';

        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ($placeholders)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error creating record: " . $e->getMessage());
            throw new \Exception("Error creating record");
        }
    }

    /**
     * Update a record
     */
    public function update($id, array $data) 
    {
        // Filter data to only include fillable fields
        $data = array_intersect_key($data, array_flip($this->fillable));
        
        if (empty($data)) {
            return false;
        }

        $fields = array_map(function($field) {
            return "$field = ?";
        }, array_keys($data));

        $sql = "UPDATE {$this->table} SET " . implode(',', $fields) . " WHERE {$this->primaryKey} = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $values = array_values($data);
            $values[] = $id;
            return $stmt->execute($values);
        } catch (\PDOException $e) {
            error_log("Error updating record: " . $e->getMessage());
            throw new \Exception("Error updating record");
        }
    }
}
