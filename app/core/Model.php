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
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$id]); // Use execute with an array for PDO
        return $stmt->fetch(\PDO::FETCH_ASSOC); // Fetch associative array
    }

    // Additional methods can be added here as needed
}
